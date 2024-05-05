<table>
    <thead>
        <?php if ($add_new == 1) {
        ?>
            <td class="<?php echo $class_hidden; ?>"></td>
        <?php
        }
        ?>

        <!-- Product name and description -->
        <td>
            <input type="checkbox" id="nw-product-name" />
        </td>
        <td colspan="<?php echo $colspan; ?>">
            <label for="nw-product-name">
                <?php echo sanitize_text_field($product_info['Description']); ?>
            </label>
        </td>
    </thead>

    <!-- List product variations on search window -->
    <tbody>
        <?php foreach ($product_info['Colors'] as $color_key => $color) {

            $disabled = $row_class = $default_datepicker_value = $formatted_date = '';
            $product_variation_status = 'publish';

            if (isset($compare[$color_key]) && count($compare[$color_key]) <= count($color['Sizes'])) {
                $disabled                 = 'disabled checked';
                $variation_id             = wc_get_product_id_by_sku($compare[$color_key][0]);
                $default_datepicker_value = get_post_meta($variation_id, 'custom_date', true);
                $product_variation_status = get_post_status($variation_id);
                $formatted_date           = date("Y-m-d\TH:i", strtotime($default_datepicker_value));
                $row_class                = 'nw-disabled-row';
            }

            $active_checkbox_attribute = ($product_variation_status == 'publish') ? 'checked' : '';

            if ($add_new == 1) {
                //Datepicker
                $date_picker = woocommerce_form_field('custom_date_field', array(
                    'type'    => 'datetime-local',
                    'required' => true,
                    'label'   => __('Sorting Date', 'woocommerce'),
                    'return'  => true,
                    'id'      => 'cdate_' . $color['Description'],
                    'default' => $formatted_date,

                ));
        ?>
                <tr class="nw-color-row <?= $row_class ?>">
                    <!-- Variant image -->
                    <td class="image-var-col img-div <?= ($add_new == 1) ?>">
                        <div class="custom-img-container ">
                            <img class="old-img" src="<?= $color_img['img'][$color_key] ?>" alt="variation image" style="max-width:42px;" />
                        </div>
                        <p class="hide-if-no-js var-image">
                            <a class="upload-custom-img" href="#">
                            <?= __('Add new image','newwave')?>
                            </a>

                            <a class="delete-custom-img hidden" href="#">
                            <?= __('Remove this image','newwave')?>
                            </a>
                        </p>
                        <input class="custom-img-id" name="custom-var-img-id" id="customImg_<?= $color['Description'] ?>" type="hidden" value="">
                    </td>
                    <!-- Variant image ends -->

                    <!-- Select cb -->
                    <td>
                        <input type="checkbox" class="product_attribute_variations" id="nw-<?= $color_key ?>" <?= $disabled ?> />
                    </td>
                    <!-- Select cb end -->

                    <!-- Variant color -->
                    <td>
                        <label for="nw-<?= $color_key ?>"><?= $color['Description']; ?></label>
                    </td>
                    <!-- Variant color end -->

                    <!-- Variant sku -->
                    <td>
                        <label class="nw-asw-sku"><?= $sku ?>-<?= $color_key ?></label>
                    </td>
                    <!-- Variant sku end -->

                    <!-- Published date -->
                    <td>
                        <?= $date_picker ?>
                    </td>
                    <!-- Published date end-->

                    <!-- Variant status -->
                    <td>
                        <label for="nw-asw-product-variant-status">Activate
                            <input type="checkbox" class="nw-asw-product-status" name="nw_asw_product_variant_status" id="nw_asw_product_variant_status_<?= $color['Description'] ?>" value="<?= $product_variation_status ?>" <?= $active_checkbox_attribute ?> />
                    </td>
                    <!-- Variant status ends -->

                    <!-- Toggle sizes -->
                    <td class="toggle-indicator"></td>
                    <!-- Toggle sizes end -->
                </tr>
            <?php
            } else {
            ?>
                <!-- Product name and description -->
                <tr class="nw-color-row <?= $row_class ?>">
                    <td>
                        <input type="checkbox" id="nw-<?= $color_key ?>" <?= $disabled ?> />
                    </td>
                    <td>
                        <label for="nw-<?= $color_key ?>"><?= $color['Description'] ?></label>
                    </td>
                    <td>
                        <label class="nw-asw-sku"><?= $sku ?>-<?= $color_key ?></label>
                    </td>
                    <td class="toggle-indicator"></td>
                </tr>
        <?php
            }

            $sizes = array();
            foreach ($color['Sizes'] as $size)
                $sizes[$size['SizeName']] = $size['SKU'];

            // Sort sizes from small to large
            uksort($sizes, 'NWP_Functions::sort_sizes');

            foreach ($sizes as $size_same => $size_sku) {
                $disabled = $row_class = '';
                if (isset($compare[$color_key]) && in_array($size_sku, $compare[$color_key])) {
                    $disabled = 'disabled checked';
                    $row_class = 'nw-disabled-row';
                }
                if ($add_new == 1) {
                    printf(
                        '<tr class="%4$s">
                            <td></td>
                            <td>
                                <input type="checkbox" id="nw-%1$s" name="nw_asw_import[%1$s]" %3$s/>
                            </td>
                            <td>
                                <label for="nw-%1$s">%2$s</label>
                            </td>
                            <td>
                                <label for="nw-%1$s">%1$s</label>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>',
                        $size_sku,
                        $size_same,
                        $disabled,
                        $row_class
                    );
                } else {
                    printf(
                        '<tr class="%4$s">
                            <td>
                                <input type="checkbox" id="nw-%1$s" name="nw_asw_import[%1$s]" %3$s/>
                            </td>
                            <td>
                                <label for="nw-%1$s">%2$s</label>
                            </td>
                            <td>
                                <label for="nw-%1$s">%1$s</label>
                            </td>
                            <td></td>
                        </tr>',
                        $size_sku,
                        $size_same,
                        $disabled,
                        $row_class
                    );
                }
            }
        } ?>
    </tbody>
</table>