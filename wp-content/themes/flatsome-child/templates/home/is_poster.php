
<?php
  $class      = 'is_poster_'.rand(5000,10000);

  $title      = get_sub_field('ps_title');
  $desc       = get_sub_field('ps_description');
  $cta        = get_sub_field('cta_link');
  $stats      = get_sub_field('stats');
  $stats_bg   = get_sub_field('stats_background');

  if( !$cta ) {
    $cta = [
      'url' => '#',
      'title' => 'REGISTRER FORESPØRSEL'
    ];
  }


  if( $stats_bg ): ?>
    <style>
      .<?php echo $class ?> .stats::before{
        content: " ";
        background-image: url('<?php echo $stats_bg['url'] ?>');
      }
    </style><?php 
  endif;
?>

<section class="is_poster <?php echo $class; ?>">
  <div class="content-wrapper">
    <div class="content"><?php

      if($title): ?>
        <p class="title"> <?php echo $title ;?> </p><?php 
      endif; 
      
      if($desc): ?>
        <p class="description"> <?php echo $desc; ?> </p><?php 
      endif; 
      
      if($cta): ?>
        <a href="<?php echo $cta['url'];?>"  class="cta_link call_req_bed">
          <?php echo $cta['title'];?>
        </a>
      <?php endif; ?>
      
    </div>
  </div>

  <div class="stats">  
        
    <?php 
      if( $stats_bg ): ?>
        <img class="stats-bg" src="<?php echo $stats_bg['url'] ?>" alt="<?php echo $stats_bg['title'] ?>"><?php 
      endif;
    ?>

    <?php foreach($stats as $key => $stat): ?>
      <div class="stats-item"><?php  

        if($stat['number']): ?>
          <p class="number"> <?php echo $stat['number'];?> </p><?php 
        endif; 

        if($stat['label']): ?>
          <p class="label"> <?php echo $stat['label'];?> </p>
          <?php 
        endif; ?>

      </div>
    <?php endforeach; ?>
  </div>
</section>