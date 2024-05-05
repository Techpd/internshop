
<?php  

  $text_1 = get_sub_field('text_block_1');
  $text_2 = get_sub_field('text_block_2');

?>


<section class="is_double_content">
  
  <div class="content-wrapper">

    <?php if($text_1): ?>
      <div class="text-block-1"><?php echo $text_1; ;?></div>
    <?php endif; ?>

    <?php if($text_2): ?>
      <div class="text-block-2"><?php echo $text_2; ;?></div>
    <?php endif; ?>

  </div>

</section>