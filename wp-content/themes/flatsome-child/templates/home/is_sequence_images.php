
<?php  

  $sqeuence = get_sub_field('sequence');
  $sub_text = get_sub_field('sub_text');
  $link     = get_sub_field('si_link');

  if( !$link ) {
    $link = [
      'url' => '#',
      'title' => 'REGISTERER DIN BEDRIFT'
    ];
  }

?>

<section class="is_sequence_images">
  <div class="sequence-container">
    <?php if($sqeuence):
      foreach($sqeuence as $key => $seq): ?>
        <div class="sequnece-item">
          <?php if($seq): ?>
            <img src="<?php echo $seq['preview_image']['url']?>" alt="" class="preview">
          <?php endif; ?>
        </div>
      <?php endforeach; 
    endif; ?>
  </div>
  
  <p class="sub-text"> <?php echo $sub_text;?> </p>

  <div class="cta">
    <?php if($link): ?>
      <a href="<?php echo $link['url'];?>"  class="cta_link call_req_bed">
        <?php echo $link['title'];?>
      </a>
    <?php endif; ?>
  </div>
</section>