<table>
    <thead>
        <!-- Product name and description -->
        <?php if ($add_new == 1) { ?>
            <td class="<?php echo $class_hidden; ?>"></td>
        <?php } ?>
        <td>
            <input type="checkbox" id="nw-product-name" />
        </td>
        <td colspan="<?php echo $colspan; ?>">
            <label for="nw-product-name">
                <?php echo sanitize_text_field($product_info['Description']); ?></label>
        </td>
    </thead>

    <!-- List product variations on import window -->
    <tbody>
        <?php foreach ($product_info['Colors'] as $color_key => $color) {
            $disabled = $row_class = '';
            $color['Description']  = sanitize_text_field($color['Description']);

            if (isset($compare[$color_key]) && count($compare[$color_key]) == count($color['Sizes'])) {
                $disabled = 'disabled checked';
                $row_class = 'nw-disabled-row';
            }

            if ($add_new == 1) {
                // Datepicker
                $date_picker = woocommerce_form_field('custom_date_field', array(
                    'type'        => 'datetime-local',
                    'required'    => true,
                    'label'       => __('Sorting Date', 'woocommerce'),
                    'return'       => true,
                    'id' => 'cdate_' . $color['Description'],
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
                    <!-- Variant image end -->

                    <!-- Select cb -->
                    <td>
                        <input type="checkbox" class="product_attribute_variations" id="nw-<?= $color_key ?>" <?= $disabled ?> />
                    </td>
                    <!-- Select cb ends -->

                    <!-- Variant color -->
                    <td>
                        <label for="nw-<?= $color_key ?>"><?= $color['Description'] ?></label>
                    </td>
                    <!-- Variant color ends -->

                    <!-- Variant SKU -->
                    <td>
                        <label class="nw-asw-sku"><?= $sku ?>-<?= $color_key ?></label>
                    </td>
                    <!-- Variant SKU ends -->

                    <!-- Published date -->
                    <td>
                        <?= $date_picker ?>
                    </td>
                    <!-- Published date ends -->

                    <!-- Variant status -->
                    <td>
                        <label for="nw-asw-product-status"><?= __('Activate','newwave')?>
                            <input type="checkbox" class="nw-asw-product-status" name="nw_asw_product_variant_status" id="nw_asw_product_variant_status_<?= $color['Description'] ?>" value="publish" checked />
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