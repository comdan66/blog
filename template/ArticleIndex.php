<!DOCTYPE html>
<html lang="zh-Hant">
  <head>
    <meta http-equiv="Content-Language" content="zh-tw">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,minimal-ui">

    <title><?php echo ($article->title ? $article->title . ' - ' : '') . TITLE;?></title>

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>css/icon.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>css/oaips.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL;?>css/public.css">

    <script language="javascript" type="text/javascript" src="<?php echo BASE_URL;?>js/jquery-1.12.4.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo BASE_URL;?>js/oaips.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo BASE_URL;?>js/public.js"></script>
  </head>
  <body>
    <input type="checkbox" id="menu-ckb" class="_ckbh">
    <input type="checkbox" id="info-ckb" class="_ckbh">
    
    <?php echo $_header;?>
    <?php echo $_menu;?>

    <main id="main"><div>

      <figure class='before-article' data-bgurl="<?php echo $article->ogImage;?>">
        <img src="<?php echo $article->ogImage;?>" />
        <figcaption><?php echo $article->description ? $article->description : $article->title;?></figcaption>
      </figure>

      <article class="panel">

        <header>
          <h1><?php echo $article->title;?></h1>
          <span><?php echo $article->bio;?></span>
        </header>
        
        <div class="info">
          <time datetime="<?php echo $article->createAt->format('Y-m-d');?>" date="<?php echo $article->createAt->format('Y.m.d');?>" pubdate="pubdate"><?php echo $article->createAt->format('Y-m-d 00:00:00');?></time>
          <span><div class="fb-like" data-href="<?php echo $article->url();?>" data-send="false" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div></span>
        </div>

        <section class="md"><?php echo $article->content;?></section>
        <div class="tags"><?php echo implode('', array_map(function($tag) { return '<a href="' . Search::url() . '?q=' . $tag . '">' . $tag . '</a>'; }, $article->tags));?></div>
        <time datetime="<?php echo $article->updateAt->format('Y-m-d');?>" date="<?php echo $article->updateAt->format('Y.m.d');?>" editdate="editdate"><?php echo $article->updateAt->format('Y-m-d 00:00:00');?></time>
      </article>
    
      <div id="other">
        <?php 
        echo implode('', array_map(function($other) {
          $aAttrs = [
            'href' => $other->url(),
          ];
          $figureAttrs = [
            'data-bgurl' => $other->iconImage,
          ];
          $imgAttrs = [
            'alt' => $other->title . ' - ' . TITLE,
            'src' => $other->iconImage
          ];

          $return = '';
          $return .= '<a' . attr($aAttrs) . '>';
            $return .= '<figure' . attr($figureAttrs) . '>';
              $return .= '<img' . attr($imgAttrs) . '/>';
              $return .= '<figcaption>' . $other->title . '</figcaption>';
            $return .= '</figure>';
            $return .= '<b>' . $other->title . '</b>';
            $return .= '<span>' . $other->description . '</span>';
          $return .= '</a>';

          return $return;
        }, $article->others()));?>
        
      </div>


      <div id="comments" class="panel"><div class="fb-comments" data-order-by="reverse_time" width="100%" data-href="<?php echo $article->url();?>" data-numposts="5"></div></div>

    </div></main>

    <?php echo $_info;?>

    <div id="fb-root"></div>
  </body>
</html>