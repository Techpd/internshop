<!-- Render import table with all product variations -->
<div class='center-div' style='float:left;margin-left:10px;margin-right:10px;width:65%;'>
    <?php
    static::render_import_table($product, $sku);
    ?>
</div>

<div class='right-div' style='float:right;margin-right:10px;width:15%;'>
    <!-- Product Brand -->
    <?php 
        if(get_option('_nw_product_brand_name')){
    ?>
    <div class='custom_tags_div pop_widget' style='border: 1px solid #ddd;padding: 6px;'>
    <?php
        woocommerce_form_field( 'product_brand', array(
            'type'        => 'text',
            'required'    => false,
            'disabled'    => 'disabled',
            'label'       => __('Product Brand', 'woocommerce'),
            'id' => 'product_brand',
            'default' => $product_brand,
            'custom_attributes' => array('readonly'=>'readonly')
        ));
    ?>
    </div>
    <?php 
        }
    ?>
    <!-- Product Brand end -->

    <!-- Product tags -->
    <div class='custom_tags_div pop_widget' style='border: 1px solid #ddd;padding: 6px;'>
        <?php
        woocommerce_form_field('custom_tags', array(
            'type'        => 'text',
            'label'       => __('Product Tags', 'woocommerce'),
            'id' => 'custom_tag',
            'description' => __('Separate tags with commas', 'woocommerce'),
            'default' => $product_custom_tags
        ));
        ?>
    </div>
    <!-- Product tags end -->

    <div class='spacer_div' style='margin-top:10px;'></div>

    <!-- Product categories -->
    <?php
    $args = array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'include' => 'all',
        'exclude' => 'all',
        'exclude_tree' => 'all',
        'hierarchical' => true,
    );

    $cat_list = get_categories($args);
    $tax_array = array();

    foreach ($cat_list as $key => $term) {
        $tax_array[$term->term_id]['name'] = $term->name;
        $tax_array[$term->parent]['child_id'][] = $term->term_id;
    }

    ?>
    <div class='custom_cat_div' style='border: 1px solid #ddd;padding: 6px;'>
        <span> <?= __('Category', 'newwave') ?> </span>
        <div class='cat_list_div'>
            <ul class='custom_cat'>
                <?php
                foreach ($tax_array[0]['child_id'] as $key => $val) {
                    if (in_array($val, $get_only_termid)) {
                ?>
                        <li>
                            <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>" checked><?= $tax_array[$val]['name'] ?>
                            <?php static::category_list_html($val, $tax_array, $get_only_termid); ?>
                        </li>
                    <?php
                    } else {
                    ?>
                        <li>
                            <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>"><?= $tax_array[$val]['name'] ?>
                            <?php static::category_list_html($val, $tax_array, $get_only_termid); ?>
                        </li>
                <?php
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <!-- Product categories end -->

    <div class='spacer_div' style='margin-top:10px;'></div>

    <!-- Product image -->
    <?php
    $product_image = '';
    $image_container_class = '';
    $product_image_id = '';
    ?>

    <div class='main_img_div img-div' style='border: 1px solid #ddd;padding: 6px;'>
        <?php
        if (isset($product_image[0])) {
        ?>
            <div class="custom-img-container <?= $image_container_class ?>">
                <img class="old-img" src="<?= $product_image[0] ?>" alt="product image" style="max-width:120px;" />
            </div>
        <?php
        } else {
        ?>
            <div class='custom-img-container'></div>
        <?php
        }
        ?>

        <div class='spacer_div' style='margin-top:10px;'> </div>

        <!-- Upload/delete custom image -->
        <p class='hide-if-no-js'>
            <a class='upload-custom-img' href='#'>
                <?= __('Set custom image', 'newwave') ?>
            </a>

            <a class='delete-custom-img hidden ' href='#'>
                <?= __('Remove this image', 'newwave') ?>
            </a>
        </p>
        <input class='custom-img-id' name='custom-img-id' id='featureImg' type='hidden' value='<?= $product_image_id ?>'>
    </div>
    <!-- Product image ends -->

    <div class='spacer_div' style='margin-top:10px;'> </div>

    <!-- Product description -->
    <div class='custom_textarea_div ' style='border: 1px solid #ddd;padding: 6px;'>
        <label> <?= __('Produkttekst', 'newwave') ?> </label>
        <textarea id='custom_textarea' name='custom_textarea' rows='3' cols='5' style='height:100px;'><?= $product_description ?></textarea>
        <p><?= __('Hvis boks forblir blank, skjer ingen endring', 'newwave') ?></p>
    </div>
    <!-- Product description ends -->

    <div class='spacer_div' style='margin-top:10px;'> </div>
    <div>
        <label> <?= __('Use new method for displaying product gallery', 'newwave') ?> </label><br>
        <input type="checkbox" name="show_slick_slider_gallery">
    </div>
</div>
</div>