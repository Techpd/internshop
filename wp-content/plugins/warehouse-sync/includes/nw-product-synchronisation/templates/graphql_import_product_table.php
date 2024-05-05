<table>
    <thead>
        <td></td>
        <td><input type="checkbox" id="nwp-product-name" /></td>
        <td colspan="5"><label for="nwp-product-name"><?php echo sanitize_text_field($product_info['data']['productById']['productName']); ?></label></td>
    </thead>

    <tbody>
        <?php
        foreach ($product_info['data']['productById']['variations'] as $color) {
            $color['itemColorName'] = sanitize_text_field($color['itemColorName']);
            $color_key = sanitize_text_field(explode('-', $color['itemNumber'])[1]);
            $disabled = $row_class = $pub_date = '';
            $product_variation_status = 'private';

            if (isset($compare[$color_key]) && count($compare[$color_key]) == count($color['skus'])) {
                $disabled = 'disabled checked';
                $row_class = 'nwp-disabled-row';
                $variation_id = wc_get_product_id_by_sku($compare[$color_key][0]);
                $product_variation_status = get_post_status($variation_id);
                $pub_date = get_post_meta($variation_id, 'custom_date', true);
            }

            $active_checkbox_attribute = ($product_variation_status == 'publish') ? 'checked' : '';

            if ($pub_date) {
                $pub_date =  date("Y-m-d\TH:i", strtotime(get_post_meta($variation_id, 'custom_date', true)));
            }

            // Datepicker  - start
            $date_picker = woocommerce_form_field('custom_date_field', array(
                'type'        => 'datetime-local',
                'required'    => true,
                'label'       => __('Published Date', 'woocommerce'),
                'return'       => true,
                'id' => 'cdate_' . $color['itemColorName'],
                'default' => $pub_date ? $pub_date : '',
            ));
            // Datepicker  - end

            $color_attr = get_term_by('name', $color['itemColorName'], 'pa_color');
            $color_attr_id = $color_attr ?  $color_attr->term_id : '';
            $color_attr_slug = $color_attr ? $color_attr->slug : '';
        ?>
            <!-- Color row -->
            <tr class="nwp-color-row <?= $row_class ?>">

                <!-- Variation image  -->
                <td class="image-var-col img-div">
                    <div class="custom-img-container ">
                        <img class="old-img" src="<?= sanitize_url($color['pictures'][0]['imageUrl']); ?>" alt="variation image" style="max-width:42px;" />
                    </div>
                    <p class="hide-if-no-js var-image">
                        <a class="upload-custom-img" href="#">
                            <?= __('Add new image', 'newwave') ?>
                        </a>

                        <a class="delete-custom-img hidden" href="#">
                            <?= __('Remove this image', 'newwave') ?>
                        </a>
                    </p>
                    <input class="custom-img-id" name="custom-var-img-id" id="customImg_<?= $color['itemColorName'] ?>" type="hidden" value="">
                </td>
                <!-- Variation image ends -->

                <!-- Variation checkbox -->
                <td>
                    <input type="checkbox" id="nwp-<?= $color_key . ' ' . $disabled ?>" name="nw_color[]" value="<?= $color_attr_id ?>" />
                </td>
                <!-- Variation checkbox ends -->

                <!-- Variation color -->
                <td>
                    <label for="nwp-<?= $color_key ?>"><?= $color['itemColorName'] ?></label>
                </td>
                <!-- Variation color ends -->

                <!-- SKU -->
                <td>
                    <label class="nwp-asw-sku"><?= $sku . '-' . $color_key ?></label>
                </td>
                <!-- SKU ends -->

                <!-- Published date -->
                <td><?= $date_picker ?></td>
                <!-- Published date ends -->

                <!-- Enable variant -->
                <td>
                    <label for="nwp-asw-product-status">
                        Activate
                    </label>
                    <input type="checkbox" class="nwp-asw-product-status" name="nwp_asw_product_variant_status" id="nwp_asw_product_variant_status_<?= $color['itemColorName'] ?>" value="<?= $product_variation_status ?>" <?= $active_checkbox_attribute ?> />
                </td>
                <!-- Enable variant ends -->

                <!-- Toggle variations -->
                <td class="toggle-indicator"></td>
                <!-- Toggle variations ends -->

            </tr>
            <!-- Color row ends -->
        <?php
            foreach ($color['skus'] as  $size_sku) {
                $disabled = $row_class = '';
                if (isset($compare[$color_key]) && in_array($size_sku['sku'], $compare[$color_key])) {
                    $disabled = 'disabled checked';
                    $row_class = 'nwp-disabled-row';
                }

                printf(
                    '<tr class="%4$s">
                        <td></td>
                        <td>
                            <input type="checkbox" id="nwp-%1$s" name="nw_asw_import[%1$s]" %3$s/>
                        </td>
                        <td>
                            <label for="nwp-%1$s">%2$s</label>
                        </td>
                        <td>
                            <label for="nwp-%1$s">%1$s</label>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>',
                    $size_sku['sku'],
                    $size_sku['skuSize']['webtext'],
                    $disabled,
                    $row_class
                );
            }
        } ?>
    </tbody>
</table>