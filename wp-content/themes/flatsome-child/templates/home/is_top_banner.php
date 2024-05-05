
<?php  
  $class      = 'top_banner_'.rand(5000,10000);
  $background = get_sub_field('banner_background');
  $title      = get_sub_field('banner_title');
  $sub_title  = get_sub_field('subtitle');

  $cta_text   = get_sub_field('banner_cta');
  if( empty($cta_text) ){
    $cta_text = "til min buttikk";
  }
  
  if( $background ): ?>
    <style>
      .<?php echo $class ?>{    
        background-image: url('<?php echo $background['url'] ?>');
      }
    </style><?php 
  endif; ?>

<section class="is_top_banner <?php echo $class; ?>">

  <div class="content">
    <p class="heading"><?php echo $title; ?></p>
    <p class="subtile"><?php echo $sub_title; ?></p>

    <?php  
      $is_logined = "";
      if ( is_user_logged_in() or WC()->session->get('nw_shop')): $is_logined = "is_logged_in";?>
        <a id="banner_pop-login" class="cta_link" href="<?php echo site_url();?>/butikk/"><?php echo $cta_text; ?></a><?php
      else:?>
        <a id="banner_pop-login" class="cta_link" href="#craft-login-form"><?php echo $cta_text; ?></a>  <?php
      endif;
    ?>

  </div>

</section>