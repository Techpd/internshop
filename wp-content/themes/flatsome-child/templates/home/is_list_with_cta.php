


<?php 

  $title = get_sub_field('title');
  $list  = get_sub_field('check_list_item');
  $cta   = get_sub_field('cta');

  if( !$cta ) {
    $cta = [
      'url' => '#craft-login-form',
      'title' => 'til min buttik'
    ];
  }

?>

<section class="is_list_with_cta">

  <?php //print_r($cta); ?>

  <p class="title"><?php echo $title;?></p>

  <div class="list-content-wrapper">

    <?php if($list): ?>
      <div class="list-wrap">
        <ul class="list">
          <?php foreach($list as $key => $item): ?>            
            <li> <?php echo $item['item_text']; ?> </li>
          <?php endforeach; ?>
        </ul>  
      </div>
    <?php endif; ?>

  </div><?php
  
  if($cta): ?>

    <?php  
      $is_logined = "";
      if ( is_user_logged_in() or WC()->session->get('nw_shop')): $is_logined = "is_logged_in";?>
        <a href="<?php echo site_url();?>/butikk/" class="cta_link"><?php echo $cta['title'];?> </a>        
        <?php
      else:?>        
        <a href="#craft-login-form" class="cta_link"><?php echo $cta['title'];?> </a>
        <?php
      endif;
    ?>
    
    <?php 
  endif; ?>

</section>
