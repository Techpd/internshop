<div class='wrapper-div'>
    <?php if (get_option('_nw_feature_properties')) : ?>
        <div class='left-div' style='float:left;margin-right:10px;'>

            <!-- Product properties -->
            <div class='custom_property_div pop_widget '>
                <?php
                if (isset($current_product_id) && $current_product_id != '') {
                    NW_Product_Property_Concept::render_panel($current_product_id);
                } else {
                    NW_Product_Property_Concept::render_panel(0);
                }
                ?>
            </div>
            <!-- Product properties end -->

            <!-- Print instructions -->
            <?php 
            if (isset($current_product_id) && $current_product_id != '') {
                $product_type = wc_get_product($current_product_id)->get_type();
                $print_instructions = get_post_meta($current_product_id,'print_instructions',true);
            }else{
                $product_type = sanitize_text_field($_POST['product_type']);
                $print_instructions = '';
            }
            
            // Add print instructions for stock item with logo
            if ($product_type == 'nw_stock_logo') {
            ?>
                <div class='nw_printing_inst_div pop_widget '>

                    <div class='spacer_div' style='margin-top:10px;'></div>

                    <div class='custom_textarea_div ' style='border: 1px solid #ddd;padding: 6px;'>
                        <label> <?= __('Trykktekst', 'newwave') ?> </label><br>
                        <textarea id='print_instructions' name='print_instructions' rows='3' cols='5' style='height:100px; width:100%'><?= $print_instructions ?></textarea>
                    </div>
                </div>
            <!-- Print instructions end -->
            <?php
            }
            ?>

            <div class='spacer_div'></div>

            <!-- Product material -->
            <div class='nw-material-div pop_widget'>
                <label for='nw-product-material'>Materiale</label>
                <input id='nw-product-material' type='text' class='input-text' value='<?= $product_meterial ?>' name='nw_product_material' style='width:100%;'>
                <p><?= __('If left empty, no change occurs.','newwave')?></p>
            </div>
            <!-- Product material end -->

            <div class='spacer_div'></div>

            <?php
            // Add product short description if the product type is nw_stock_logo or nw_special
            if ($product_type == 'nw_stock_logo' || $product_type == 'nw_special') {
            ?>
                <!-- Short description -->
                <div class='spacer_div' style='margin-top:10px;'> </div>

                <div class='custom_textarea_div ' style='border: 1px solid #ddd;padding: 6px;'>
                    <label> <?= __('Kort beskrivelse', 'newwave') ?> </label><br>
                    <textarea id='short_description' name='short_description' rows='3' cols='5' style='height:100px; width:100%'></textarea>
                </div>
                <!-- Short description end -->
                <?php
                }
            ?>

            <div class='spacer_div'></div>

            <!-- Product attribut icons -->
            <div class='nw_attribute_icon_div pop_widget '>
                <?php
                if (isset($current_product_id) && $current_product_id != '') {
                    NW_Product_Property_Attribute_Icons::render_panel($current_product_id);
                } else {
                    NW_Product_Property_Attribute_Icons::render_panel(0);
                }
                ?>
            </div>
            <!-- Product attribut icons end -->
        </div>
    <?php endif; ?>

    <div class='center-div' style='float:left;margin-left:10px;margin-right:10px;'>

        <!-- Product variations table -->
        <?php
        if (isset($current_product_id) && $current_product_id != '') {
            static::render_import_table_with_existing_data($product, $sku, $existing_skus, 1, $current_product_id);
        } else {
            static::render_import_table($product, $sku, [], 1);
        }
        ?>
        <!-- Product variations table end -->

    </div>

    <div class='right-div' style='float:right;margin-right:10px;'>
        <!-- Product Brand -->
        <?php if(get_option('_nw_product_brand_name')){ ?>
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
        <?php } ?>
        <!-- Product Brand end -->

        <!-- Product tags -->
        <div class='custom_tags_div pop_widget'>
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

        <div class='spacer_div'></div>

        <!-- Product categories -->
        <div class='custom_cat_div pop_widget'>
            <span><?= __('Categories','newwave')?> </span>
            <div class='cat_list_div'>
                <ul class='custom_cat'>
                    <?php
                    $args = array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false, //can be 1, '1' too
                        'include' => 'all', //empty string(''), false, 0 don't work, and return empty array
                        'exclude' => 'all', //empty string(''), false, 0 don't work, and return empty array
                        'exclude_tree' => 'all', //empty string(''), false, 0 don't work, and return empty array
                        'hierarchical' => true,
                    );

                    $cat_list = get_categories($args);
                    $tax_array = array();
                    foreach ($cat_list as $key => $term) {
                        $tax_array[$term->term_id]['name'] = $term->name;
                        $tax_array[$term->parent]['child_id'][] = $term->term_id;
                    }
                    foreach ($tax_array[0]['child_id'] as $key => $val) {
                        if (in_array($val, $get_only_termid)) {
                    ?>
                            <li>
                                <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox" checked><?= $tax_array[$val]['name'] ?>
                                <?php
                                static::category_list_html($val, $tax_array, $get_only_termid);
                                ?>
                            </li>
                        <?php
                        } else {
                        ?>
                            <li>
                                <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox"><?= $tax_array[$val]['name'] ?>
                                <?php
                                static::category_list_html($val, $tax_array, $get_only_termid);
                                ?>
                            </li>
                    <?php
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        <!-- Product categories end -->

        <div class='spacer_div'> </div>

        <!-- Featured image -->
        <div class='main_img_div pop_widget img-div'>
            <?php
            if (isset($product_image[0])) {
            ?>
                <div class='custom-img-container <?= $image_container_class ?>'>
                    <img class='old-img' src='<?= $product_image[0] ?>' alt='product image' style='max-width:120px;' />
                </div>
            <?php
            } else {
            ?>
                <div class='custom-img-container'></div>
            <?php
            }
            ?>
            <p class='hide-if-no-js'>
                <a class='upload-custom-img' href='#'>
                <?= __('Set custom image','newwave')?>
                </a>

                <a class='delete-custom-img hidden ' href='#'>
                <?= __('Remove this image','newwave')?>
                </a>
            </p>
            <input class='custom-img-id' name='custom-img-id' id='featureImg' type='hidden' value='<?= $product_image_id ?>'>
        </div>
        <!-- Featured image end -->

        <div class='spacer_div'> </div>

        <!-- Product short description -->
        <div class='custom_textarea_div pop_widget '>
            <label> <?= __('Product text','newwave')?> </label>
            <textarea id='custom_textarea' name='custom_textarea' rows='3' cols='5' style='height:100px;'><?= $product_description ?></textarea>
            <p><?= __('If left empty, no change occurs.','newwave')?></p>
        </div>
        <!-- Product short description end -->
    </div>
</div>