


<?php
  $items = get_sub_field('items');

  
  if( !empty($items) ): ?>
    <section class="is_showcase">
      
      <div class="items-wrapper"><?php       
        $count = 0;
        foreach ($items as $key => $item):          
          // print_r($item['link']);
          if( true ): ?>          
            <div class="showcase_item">

              <div class="preview">
                <img src="<?php echo $item['sc_image']['url']; ?>" alt="<?php echo $item['sc_image']['title']; ?>">
              </div><?php 
                          
              if($item['sc_description']): ?>
                <p class="description"><?php echo $item['sc_description']; ?></p><?php 
              endif; 
              
              if($item['link']): ?>
                <a href="<?php echo $item['link']['url'];?>"  class="cta_link" <?php if($item['link']['target']):?> target="_blank" <?endif;?>>
                  <?php echo $item['link']['title'];?>
                </a>
              <?php endif; ?>
            </div><?php 
          endif;
        endforeach; ?>
      </div>

    </section><?php 
  endif; 

?>
