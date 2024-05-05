

<?php  

  $testimonails = get_sub_field('testimonial');

?>


<section class="is_testimonials">

  <div class="testimonial-wrapper">
    <?php if($testimonails): ?>      
      <?php foreach($testimonails as $key => $value): ?>
        <div class="feedback-item">  

          <div class="avatar">
            <?php if($value['avatar']): ?>
              <img src="<?php echo $value['avatar']['url'] ?>" alt="" class="preview">
            <?php endif; ?>
          </div>

          <?php if($value['feedback']): ?>
            <p class="feedback">
              <?php echo $value['feedback'];?>
            </p>
          <?php endif; ?>

          <?php if($value['name']): ?>
            <span class="username"><?php echo '- '.$value['name'];?> </span>  
          <?php endif; ?>          
        </div>
      <?php endforeach; ?>  
    <?php endif; ?>
  </div>

</section>