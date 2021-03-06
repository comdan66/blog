<?php

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Ginkgo
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Album extends Item {
  public $images = [];

  protected function coverImages() {
    $images = $this->searchImages();

    $pattern = '/<img.*?src=(["\'])(.*?)\1(.*?alt=(["\'])(.*?)\1)?([^\>]*)>/u';

    $this->content = preg_replace_callback($pattern, function($matches) use ($images) {
      if (isset($images[$matches[2]]) && $images[$matches[2]] != D4_IMG_URL)
        array_push($this->images, [
          'src' => $images[$matches[2]],
          'alt' => $matches[5]
        ]);
      return '';
    }, $this->content);

    $pattern = '/<p[^>]*?>\s*<\/p>/u';
    $this->content = preg_replace($pattern, '', $this->content);

    $this->images = array_values($this->images);
    $oasort = oasort(count($this->images));

    foreach ($this->images as $i => &$image)
      $image['class'] = isset($oasort[$i]) ? $oasort[$i] : 'n11';

    return $this;
  }
  
  public function write() {
    return fileWrite($this->writePath(), loadView(PATH_TEMPLATE . 'Album.php', [
      '_Checkbox' => loadView(PATH_TEMPLATE . '_Checkbox.php'),
      '_Header' => loadView(PATH_TEMPLATE . '_Header.php', ['item' => $this]),
      '_Menu' => loadView(PATH_TEMPLATE . '_Menu.php', ['currentUrl' => $this->currentUrl()]),
      '_Info' => loadView(PATH_TEMPLATE . '_Info.php'),
      'article' => $this,
    ]));
  }
}