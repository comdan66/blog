<?php

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Ginkgo
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

abstract class Item extends Menu {
  const INDEX_MD = 'readme.md';
  const IMG_FORMATS = ['jpg', 'jpeg', 'gif', 'png'];

  private static $groups = [];
  private static $all = [];
  private static $sitemaps = null;

  protected $markdownPath, $fileName, $uris = [], $page = null, $items = null;
  public $title, $description, $bio, $ogImage, $iconImage, $content, $tags, $createAt, $updateAt;

  public function setPage(Page $page) { $this->page = $page; return $this; }
  public function setMarkdownPath($markdownPath) { $this->markdownPath = $markdownPath; return $this; }
  public function setHtmlName($fileName) { $this->fileName = $fileName; return $this; }
  public function setUris($uris) { $this->uris = $uris; return $this; }
  public function setItems(Items $items) { $this->items = $items; return $this; }

  public static function init($markdownPath, $fileName, $uris, $createAt = null) {
    
    array_key_exists($key = implode('_', $uris), Item::$groups) || Item::$groups[$key] = [];

    $i = '';
    while(true) {
      if (!Item::existsByUris($fileName . $i, $key))
        break;
      $i = $i ? ++$i : 2;
    }

    $obj = new static();
    $obj->setMarkdownPath($markdownPath);
    $obj->setHtmlName($fileName . $i);
    $obj->setUris($uris);
    $obj->createAt = $createAt;
    $obj->setCurrentUrl($obj->url());

    return Item::append($key, $obj);
  }

  public function markdownPath() { return $this->markdownPath; }
  public function fileName() { return $this->fileName; }
  public function uris() { return $this->uris; }
  
  public function url() { return BASE_URL . ($this->uris ? implode('/', array_map('rawurlencode', $this->uris)) . '/' : '') . rawurlencode($this->fileName) . '.html'; }
  public function writePath() { return PATH . ($this->uris ? implode(DIRECTORY_SEPARATOR, $this->uris) . DIRECTORY_SEPARATOR : '') . $this->fileName . '.html'; }
  public function page() { return $this->page; }
  public function items() { return $this->items; }

  public function sitemap() {
    return [
      'type' => $this instanceof Album ? 'album' : 'article',
      'loc' => $this->url(),
      'priority' => $this->uris() ? '0.7' : '0.3',
      'changefreq' => 'daily',
      'lastmod' => $this->updateAt->format('c'),
      'images' => isset($this->images) ? array_slice(array_map(function($image) {
        return [
          'loc' => $image['src'],
          'title' => $this->title,
          'caption' => $image['alt'] ? $image['alt'] : $this->title,
          'license' => License::url(),
        ];
      }, $this->images), 0, 1000) : []
    ];
  }

  public static function existsByUris($fileName, $key = null) {
    $groups = array_key_exists($key, Item::$groups) ? [Item::$groups[$key]] : Item::$groups;

    foreach ($groups as $group)
      foreach ($group as $item)
        if ($item->fileName() == $fileName)
          return true;

    return false;
  }

  public static function append($key, Item $item) {
    array_push(Item::$groups[$key], $item);
    Item::$all[$item->markdownPath()] = $item;
    return $item;
  }

  public static function existsByMarkdownPath($markdownPath = null) {
    return array_key_exists($markdownPath, Item::$all) ? Item::$all[$markdownPath] : null;
  }

  public static function modifyAllContent() {
    foreach (Item::$groups as $key => $items)
      foreach ($items as $item)
        $item->modifyContent();
  }

  public static function groups() {
    return Item::$groups;
  }

  public static function sitemaps($key = null) {
    Item::$sitemaps !== null || Item::$sitemaps = array_values(array_filter(array_map(function($item) {
      $sitemap = $item->sitemap();
      
      if (empty($sitemap['loc']))
        return null;

      if ($sitemap['type'] == 'album' && !($sitemap['images'] = array_values(array_filter($sitemap['images'], function($image) { return !empty($image['loc']); }))))
        return null;

      return $sitemap;
    }, Item::all())));

    $sitemaps = $key === null ? Item::$sitemaps : array_filter(Item::$sitemaps, function($sitemap) use ($key) {
      return isset($sitemap['type']) && $sitemap['type'] == $key;
    });

    return array_values(array_map(function($sitemap) {
      unset($sitemap['type']);
      return $sitemap;
    }, $sitemaps));
  }

  public static function all() {
    return Item::$all;
  }

  public function modifyContent() {
    return $this->getContent()
                ->coverContentToHtml()
                ->coverImages()
                ->coverLinks()
                ->getContentModifyAt()
                ->getContentTags()
                ->getContentOgImage()
                ->getContentIconImage()
                ->getContentDescription()
                ->getContentTitle()
                ->removeContentLn()
                ;
  }

  protected function getContent() {
    $this->content = fileRead($this->markdownPath() . Item::INDEX_MD);
    return $this;
  }

  protected function coverContentToHtml() {
    $parsedown = new Parsedown();
    $this->removeContentLn();
    $this->content = $parsedown->text($this->content);
    $this->replaceContentSpace('/<!--(.*)-->/u');
    return $this;
  }

  protected function replaceContentSpace($pattern, $removeLn = true) {
    $this->content = preg_replace($pattern, '', $this->content);
    return $removeLn ? $this->removeContentLn() : $this;
  }

  protected function removeContentLn() {
    return $this->replaceContentSpace('/^[\n ]*|[\n ]*$/u', false);
  }

  private function moveImage2Asset($file) {
    if (!defined('PATH_ASSET'))
      return BASE_URL . implode('/', array_map('rawurlencode', explode(DIRECTORY_SEPARATOR, pathReplace(PATH, $file))));

    $dest = PATH_ASSET . md5_file($file) . '.' . pathinfo($file, PATHINFO_EXTENSION);
    file_exists($dest) && is_readable($dest) || @copy($file, $dest);
    
    if (!is_readable($dest)) {
      if (CHECK_IMAGE_EXIST)
        throw new Exception(json_encode([
          '錯誤原因' => '搬移圖片至 Asset 錯誤！',
          '檔案位置' => pathReplace(PATH, $this->markdownPath() . Item::INDEX_MD),
          '圖片位置' => pathReplace(PATH, $dest)
        ]));

      return D4_IMG_URL;
    }

    return BASE_URL . implode('/', array_map('rawurlencode', explode(DIRECTORY_SEPARATOR, pathReplace(PATH, $dest))));
  }
  protected function searchImages() {
    $pattern = '/<img.*?src=(["\'])(?P<imgs>.*?)\1[^\>]*>/u';

    $images = preg_match_all($pattern, $this->content, $matches) ? array_filter(array_map(function($img) {
      if (preg_match('/^https?:\/\/.*/ui', $img) || preg_match('/^mailto:/ui', $img) || preg_match('/^tel:/ui', $img) || preg_match('/^s?ftp:/ui', $img)) {
        return [
          'search' => $img,
          'replace' => $img,
        ];
      }

      if (!(($file = realpath($this->markdownPath() . $img)) && is_readable($file))) {
        if (CHECK_IMAGE_EXIST)
          throw new Exception(json_encode([
            '錯誤原因' => '圖片遺失，找不到圖片！',
            '檔案位置' => pathReplace(PATH, $this->markdownPath() . Item::INDEX_MD),
            '圖片位置' => $img
          ]));

        return [
          'search' => $img,
          'replace' => D4_IMG_URL,
        ];
      }

      $src = $this->moveImage2Asset($file);

      return [
        'search' => $img,
        'replace' => $src
      ];
    }, array_unique(array_filter($matches['imgs'], function($t) {
      return $t;
    })))) : [];

    $tmps = [];
    foreach ($images as $image)
      if (!isset($tmps[$image['search']]))
        $tmps[$image['search']] = $image['replace'];

    return $tmps;
  }

  protected function coverImages() {
    $images = $this->searchImages();

    $pattern = '/<img.*?src=(["\'])(.*?)\1(.*?alt=(["\'])(.*?)\1)?([^\>]*)>/u';

    $this->content = preg_replace_callback($pattern, function($matches) use ($images) {
      $attrs = [
        'src' => isset($images[$matches[2]]) ? $images[$matches[2]] : D4_IMG_URL,
        'alt' => $matches[5]
      ];
      return '<img' . attr($attrs) . '>';
    }, $this->content);

    return $this;
  }

  protected function searchLinks() {
    $pattern = '/<a.*?href=(["\'])(?P<hrefs>.*?)\1[^\>]*>/u';
    
    $links = preg_match_all($pattern, $this->content, $matches) ? array_filter(array_map(function($href) {
      if (preg_match('/^https?:\/\/.*/ui', $href) || preg_match('/^mailto:/ui', $href) || preg_match('/^tel:/ui', $href) || preg_match('/^s?ftp:/ui', $href))
        return [
          'search' => $href,
          'replace' => $href,
        ];

      if (!(($search = realpath($this->markdownPath() . $href)) && is_readable($search))) {
        if (CHECK_LINK_EXIST)
          throw new Exception(json_encode([
            '錯誤原因' => '鏈結遺失，找不到鏈結！',
            '檔案位置' => pathReplace(PATH, $this->markdownPath() . Item::INDEX_MD),
            '鏈結字串' => $href
          ]));

        return [
          'search' => $href,
          'replace' => Page404::url()
        ];
      }

      $search = (is_dir($search) ? $search : dirname($search)) . DIRECTORY_SEPARATOR;

      if (($tmp = Items::existsByMarkdownPath($search)) !== null)
        return [
          'search' => $href,
          'replace' => $tmp->url()
        ];

      if (($tmp = Item::existsByMarkdownPath($search)) !== null)
        return [
          'search' => $href,
          'replace' => $tmp->url()
        ];
      
      if (CHECK_LINK_EXIST)
        throw new Exception(json_encode([
          '錯誤原因' => '鏈結遺失，找不到鏈結！',
          '檔案位置' => pathReplace(PATH, $this->markdownPath() . Item::INDEX_MD),
          '鏈結字串' => $href
        ]));

      return [
        'search' => $href,
        'replace' => BASE_URL . '404.html'
      ];
    }, array_unique(array_filter($matches['hrefs'], function($t) {
      return $t;
    })))) : [];

    $tmps = [];
    foreach ($links as $link)
      if (!isset($tmps[$link['search']]))
        $tmps[$link['search']] = $link['replace'];
   
    return $tmps;
  }

  protected function coverLinks() {
    $links = $this->searchLinks();

    $pattern = '/<a.*?href=(["\'])(?P<hrefs>.*?)\1[^\>]*>/u';
    
    $this->content = preg_replace_callback($pattern, function($matches) use ($links) {
      return '<a href=' . $matches[1] . (isset($links[$matches[2]]) ? $links[$matches[2]] : $matches[2]) . $matches[1] . '>';
    }, $this->content);

    return $this;
  }

  protected function getContentModifyAt() {
    $pattern = '/<p[^>]*?>(?P<updateAt>\d\d\d\d[-.](0?[1-9]|1[0-2])[-.](0?[1-9]|[12][0-9]|3[01])( (00|[0-9]|1[0-9]|2[0-3]):([0-9]|[0-5][0-9])(:([0-9]|[0-5][0-9]))?)?)<\/p>$/u';

    if ($this->updateAt = preg_match_all($pattern, $this->content, $matches) && isset($matches['updateAt'][0]) ? $matches['updateAt'][0] : '')
      $this->replaceContentSpace($pattern);

    if ($this->updateAt)
      foreach (['Y-m-d', 'Y.m.d', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y.m.d H:i', 'Y.m.d H:i:s'] as $format)
        if (($tmp = DateTime::createFromFormat($format, $this->updateAt)) !== false && ($this->updateAt = $tmp))
          break;

    $this->updateAt instanceof DateTime || $this->updateAt = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', filemtime($this->markdownPath() . Item::INDEX_MD)));
    $this->updateAt instanceof DateTime || $this->updateAt = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
    $this->createAt instanceof DateTime || $this->createAt = $this->updateAt;

    return $this;
  }

  protected function getContentTags() {
    $this->tags = [];

    $pattern = '/<p.*?>\s*(?P<tags><code.*?>.+?<\/code>)\s*<\/p>$/u';
    if (!$tags = preg_match_all($pattern, $this->content, $matches) && isset($matches['tags'][0]) ? $matches['tags'][0] : [])
      return $this;

    $tags = implode('</code><code>', (explode('``', $tags)));

    if (!$tags = preg_match_all('/<code.*?>#(?P<tags>.+?)<\/code>/u', $tags, $matches) ? $matches['tags'] : [])
      return $this;

    $this->tags = array_filter(array_map(function($tag) { return trim($tag); }, $tags));
    $this->replaceContentSpace($pattern);

    return $this;
  }

  protected function getContentOgImage() {
    $this->ogImage = D4_IMG_URL;

    foreach (Item::IMG_FORMATS as $format)
      if (file_exists($file = $this->markdownPath() . 'cover.' . $format) && is_readable($file))
        $this->ogImage = $this->moveImage2Asset($file);

    return $this;
  }

  protected function getContentIconImage() {
    $this->iconImage = $this->ogImage;

    foreach (Item::IMG_FORMATS as $format)
      if (file_exists($file = $this->markdownPath() . 'icon.' . $format) && is_readable($file))
        $this->iconImage = $this->moveImage2Asset($file);

    return $this;
  }

  protected function getContentDescription() {
    $this->bio = '';
    $this->description = DESCRIPTION;
    $pattern = '/^<p[^>]*?>(?P<desc>.*)<\/p>/u';

    if ($this->bio = preg_match_all($pattern, $this->content, $matches) && isset($matches['desc'][0]) ? $matches['desc'][0] : '') {
      $this->replaceContentSpace($pattern);
      $this->description = $this->bio;
    } else {
      $this->description = $this->content;
    }

    $this->bio && $this->bio = mb_strimwidth(removeHtmlTag($this->description), 0, 300, '…','UTF-8');
    $this->description = mb_strimwidth(removeHtmlTag($this->description), 0, 300, '…','UTF-8');
    return $this;
  }

  protected function getContentTitle() {
    $this->title = TITLE;
    $pattern = '/^<h1[^>]*?>(?P<title>.*)<\/h1>/u';

    if ($this->title = preg_match_all($pattern, $this->content, $matches) && isset($matches['title'][0]) ? $matches['title'][0] : '')
      $this->replaceContentSpace($pattern);

    return $this;
  }
  
  abstract public function write();
}