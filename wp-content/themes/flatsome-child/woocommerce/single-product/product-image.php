<?php

/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
						 
 * @package WooCommerce\Templates
 * @version 7.8.0
 */
defined('ABSPATH') || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (!function_exists('wc_get_gallery_image_html')) {
    return;
}

global $post, $product;

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'change_variant_gallery') {
    if (isset($_REQUEST['post_id']) && $_REQUEST['post_id'] > 0) {
        $post = get_post($_REQUEST['post_id']);
        setup_postdata($post);
        $product = wc_get_product($_REQUEST['post_id']);
    } else {
        $result['error'] = 'error';
        echo json_encode($result);
        die();
    }
}

//display_r($post, 'vr', false);
//display_r($product, 'vr', false);
?>
<?php
if ($product->get_type() == 'gift-card') {
    $columns           = apply_filters('woocommerce_product_thumbnails_columns', 4);
    $post_thumbnail_id1 = $product->get_image_id();
    $wrapper_classes   = apply_filters('woocommerce_single_product_image_gallery_classes', array(
        'woocommerce-product-gallery',
        'woocommerce-product-gallery--' . (has_post_thumbnail() ? 'with-images' : 'without-images'),
        'woocommerce-product-gallery--columns-' . absint($columns),
        'images',
    ));
?>
    <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?>" data-columns="<?php echo esc_attr($columns); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
        <figure class="woocommerce-product-gallery__wrapper">
            <?php
            if (has_post_thumbnail()) {
                $html  = wc_get_gallery_image_html($post_thumbnail_id1, true);
            } else {
                $html  = '<div class="woocommerce-product-gallery__image--placeholder">';
                $html .= sprintf('<img src="%s" alt="%s" class="wp-post-image" />', esc_url(wc_placeholder_img_src()), esc_html__('Awaiting product image', 'woocommerce'));
                $html .= '</div>';
            }

            echo apply_filters('woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id1);

            do_action('woocommerce_product_thumbnails');
            ?>
        </figure>
    </div>
<?php } else { ?>
    <!-- <div class="left_img_wrap"> -->
    <div class="images">
        <div class="badges">
            <?php do_action('custom_badges'); ?>
        </div>
        <div class="container">
            <div class="row">
                <?php
                $is_color_set = false;
                $dummy_image = get_template_directory_uri() . '/images/dummy.png';
                $post_thumbnail_url = $dummy_image;

                if (has_post_thumbnail()) {
                    $post_thumbnail_url = get_the_post_thumbnail_url();
                }

                if ($product->is_type('variable')) {
                    if (isset($_REQUEST['attribute_pa_color']) && !empty($_REQUEST['attribute_pa_color'])) {
                        $selected_color = $_REQUEST['attribute_pa_color'];
                        $is_color_set = true;
                    } else {
                        // $color_terms = wc_get_product_terms(get_the_ID(), 'pa_color' );
                        // $selected_color = $color_terms[0]->slug;

                        // get product colors that are in stock 
                        $available_colors = array();
                        foreach ($product->get_available_variations() as $variation) {
                            $attributes = $variation['attributes'];
                            if ($variation['is_in_stock']) {
                                if (isset($attributes['attribute_pa_color'])) {

                                    $color = $attributes['attribute_pa_color'];
                                    if (!in_array($color, $available_colors)) {
                                        $available_colors[] = $color;
                                    }
                                }
                            }
                        }

                        // sort colors by name
                        sort($available_colors);

                        $terms = wc_get_product_terms($product->get_id(), 'pa_color', array('fields' => 'all'));
                        $slugs = array_map(function ($term) {
                            return $term->slug;
                        }, $terms);

                        if (isset($slugs[0]) && in_array($slugs[0], $available_colors)) {
                            $selected_color = $slugs[0];
                        } else {
                            $selected_color = $available_colors[0];
                        }
                    }
                }

                $i_count = 0;
                $attachment_ids = $product->get_gallery_image_ids();
                $color_images = nw_get_color_attribute_images($product->get_id());
                $variations = array();
                if ($product->is_type('variable')) {
                    $variations = $product->get_available_variations();
                    $variations = array_reverse($variations);
                }

                if ($product->is_type('variable') && get_post_meta($product->get_id(), '_show_slick_slider_gallery', true) == 1) {
                    $attachment_ids = array();
                    if (have_rows('color_variants_gallery')) {
                        while (have_rows('color_variants_gallery')) {
                            the_row();
                            $product_color = get_sub_field('product_color');
                            if ($product_color > 0) {
                                $color_term = get_term($product_color);
                                $color_gallery = get_sub_field('color_gallery');
                                if ($color_gallery && ($selected_color == $color_term->slug)) {
                                    foreach ($color_gallery as $gallery_image) {
                                        $attachment_ids[] = $gallery_image['id'];
                                    }
                                    break;
                                }
                            }
                        }
                    } else {
                        $attachment_ids = $product->get_gallery_image_ids();
                    }
                }

                $attachment_count = count($attachment_ids);
                if (has_post_thumbnail()) {
                    $i_count++;
                }

                $is_color_selected = false;
                if ($product->is_type('variable')) {
                    $thumb_vars_full = array();
                    $selected_variation = '';
                    foreach ($variations as $variation) {
                        if (array_key_exists($variation['attributes']['attribute_pa_color'], $color_images)) {
                            $thumb_vars_full[$variation['attributes']['attribute_pa_color']] = $variation;
                            $i_count++;

                            if (!empty($_REQUEST['attribute_pa_color']) && $_REQUEST['attribute_pa_color'] == $variation['attributes']['attribute_pa_color']) {
                                $is_color_selected = $variation['attributes']['attribute_pa_color'];
                            }
                        }

                        if ($variation['attributes']['attribute_pa_color'] == $selected_color) {
                            $selected_variation = $variation;
                        }
                    }

                    //            display_r($color_images, 'pr', false);
                    //            display_r($selected_variation, 'pr', false);
                }

                //        display_r($is_color_selected, 'vr', false);

                if (($attachment_count + $i_count) > 1) {
                ?>
                    <div class="col-md-10 col-sm-10 col-md-push-2 col-sm-push-2 custom-width-thumb">
                        <div class="slider-for single_thumb_wrap">
                            <?php
                            $loop = 0;
                            $columns = apply_filters('woocommerce_product_thumbnails_columns', 3);
                            if (!$is_color_selected && has_post_thumbnail() && !$product->is_type('variable')) {
                                $image_title = esc_attr(get_the_title(get_post_thumbnail_id()));
                                $image_caption = get_post(get_post_thumbnail_id())->post_excerpt;
                                $image_link = wp_get_attachment_url(get_post_thumbnail_id());
                                $image = get_the_post_thumbnail($post->ID, apply_filters('single_product_small_thumbnail_size', 'full'), array(
                                    'title' => $image_title,
                                    'alt' => $image_title
                                ));
                                $classes[] = 'first';
                                echo '<div class="item">';
                                echo '<div class="item-inner img-first easyzoom">';
                                echo '<a href="' . $image_link . '" class="mfp-image" data-rel="prettyPhoto">';
                                echo '<img class="img-responsive" src="' . $image_link . '">';
                                echo '</a>';
                                echo '</div>';
                                echo '</div>';
                            }

                            if ($product->is_type('variable')) {

                                $image_link = isset($selected_variation['image']['full_src']) ? $selected_variation['image']['full_src'] : $post_thumbnail_url;

                                echo '<div class="item">';
                                echo '<div class="item-inner img-first-new easyzoom">';
                                echo '<a href="' . $image_link . '" class="mfp-image" data-rel="prettyPhoto">';
                                echo '<img class="img-responsive" src="' . $image_link . '">';
                                echo '</a>';
                                echo '</div>';
                                echo '</div>';

                                foreach ($attachment_ids as $attachment_id) {
                                    $img = wp_get_attachment_url($attachment_id);
                                    echo '<div class="item easyzoom"><a href="' . $img . '" class="mfp-image img-mfp-new" data-rel="prettyPhoto"><img class="img-responsive " src="' . $img . '"></a></div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-sm-12">
                        <div class="single_thumb_wrap no-slider-nav easyzoom">
                            <?php
                            //                    $dummy_image = get_template_directory_uri() . '/images/dummy.png';
                            if (has_post_thumbnail() && !$product->is_type('variable')) {
                                $image_title = esc_attr(get_the_title(get_post_thumbnail_id()));
                                $image_caption = get_post(get_post_thumbnail_id())->post_excerpt;
                                $image_link = wp_get_attachment_url(get_post_thumbnail_id());
                                $image = get_the_post_thumbnail($post->ID, apply_filters('single_product_large_thumbnail_size', 'full'), array(
                                    'title' => $image_title,
                                    'alt' => $image_title
                                ));

                                $attachment_count = count($product->get_gallery_image_ids());

                                if ($attachment_count > 0) {
                                    $gallery = '[product-gallery]';
                                } else {
                                    $gallery = '';
                                }
                                echo '<a href="' . $image_link . '" class="mfp-image img-mfp-no-slider" data-rel="prettyPhoto">';
                                echo '<img class="img-responsive" src="' . $image_link . '">';
                                echo '</a>';
                            } elseif ($product->is_type('variable')) {
                                $image_link = isset($selected_variation['image']['full_src']) ? $selected_variation['image']['full_src'] : $post_thumbnail_url;
                                echo '<a href="' . $image_link . '" class="mfp-image img-mfp-no-slider-new" data-rel="prettyPhoto">';
                                echo '<img class="img-responsive" src="' . $image_link . '">';
                                echo '</a>';
                            } else {
                                echo '<img class="img-responsive" src="' . $dummy_image . '">';
                            }
                            ?>
                        </div>
                        <script>
                            jQuery(document).ready(function() {
                                if (jQuery('.single-product.woocommerce div.product .no-slider-nav').length) {
                                    jQuery('.single-product.woocommerce div.product').addClass('no-slider-nav');
                                }
                            });
                        </script>
                    </div>
                <?php } ?>
                <div class="col-md-2 col-sm-2 col-md-pull-10 col-sm-pull-10 slider_nav_cont custom-width">
                    <div class="slider-nav">
                        <?php
                        $i_count = 0;

                        if (has_post_thumbnail()) {
                            $i_count++;
                        }

                        if (($attachment_count + $i_count) > 1) {
                            $loop = 0;
                            $columns = apply_filters('woocommerce_product_thumbnails_columns', 3);
                            if (!$is_color_selected && has_post_thumbnail() && !$product->is_type('variable')) {
                                $image_title = esc_attr(get_the_title(get_post_thumbnail_id()));
                                $image_caption = get_post(get_post_thumbnail_id())->post_excerpt;
                                $image_link = wp_get_attachment_url(get_post_thumbnail_id());
                                $image = get_the_post_thumbnail($post->ID, apply_filters('single_product_small_thumbnail_size', 'full'), array(
                                    'title' => $image_title,
                                    'alt' => $image_title
                                ));
                                $classes[] = 'first';
                                echo '<div class="item">';
                                echo '<div class="item-img img-1" style="background-image: url(' . $image_link . ')"></div>';
                                echo '</div>';
                            } elseif ($product->is_type('variable')) {
                                $image_link = isset($selected_variation['image']['full_src']) ? $selected_variation['image']['full_src'] : $post_thumbnail_url;
                                echo '<div class="item">';
                                echo '<div class="item-img img-1-new" style="background-image: url(' . $image_link . ')"></div>';
                                echo '</div>';
                            }

                            if (!$is_color_selected && !$product->is_type('variable')) {
                                foreach ($attachment_ids as $attachment_id) {
                        ?>
                                    <div class="item">
                                        <?php
                                        $classes = array();

                                        if ($loop === 0 || $loop % $columns === 0)
                                            $classes[] = 'first';

                                        if (($loop + 1) % $columns === 0)
                                            $classes[] = 'last';

                                        $image_link = wp_get_attachment_url($attachment_id);

                                        if (!$image_link)
                                            continue;

                                        $image_title = esc_attr(get_the_title($attachment_id));
                                        $image_caption = esc_attr(get_post_field('post_excerpt', $attachment_id));

                                        $image = wp_get_attachment_image($attachment_id, apply_filters('single_product_small_thumbnail_size', 'full'), 0, $attr = array(
                                            'title' => $image_title,
                                            'alt' => $image_title
                                        ));

                                        $image_class = esc_attr(implode(' ', $classes));
                                        echo '<div class="item-img img-2" style="background-image: url(' . $image_link . ')"></div>';

                                        $loop++;
                                        ?>
                                    </div>
                                <?php
                                }
                            } elseif ($product->is_type('variable')) {
                                foreach ($attachment_ids as $attachment_id) {
                                ?>
                                    <div class="item">
                                        <?php
                                        $image_link = wp_get_attachment_url($attachment_id);

                                        if (!$image_link)
                                            continue;

                                        echo '<div class="item-img img-2-new" style="background-image: url(' . $image_link . ')"></div>';
                                        ?>
                                    </div>
                        <?php
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                /*Commenting this code from this file*/
                jQuery('.slider-for').slick({
                    slidesToScroll: 1,
                    arrows: true,
                    infinite: false,
                    easing: 'easeInOutQuint', // Easing for the free mode effect
                    speed: 500, // Animation speed
                    cssEase: 'ease', // CSS easing for the free mode effect
                    // prevArrow: '<div class="slick-prev"><div>',
                    // nextArrow: '<div class="slick-next"><div>',
                    fade: true,
                    dots: true,
                    asNavFor: '.slider-nav',
                    swipe: true
                });

                // for slider thubmnail
                jQuery('.slider-nav').slick({
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    asNavFor: '.slider-for',
                    easing: 'easeInOutQuint', // Easing for the free mode effect
                    speed: 500, // Animation speed
                    cssEase: 'ease', // CSS easing for the free mode effect
                    // vertical: true,
                    arrows: true,
                    infinite: false,
                    focusOnSelect: true,
                    swipe: true,
                    responsive: [
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 3,
                            }
                        },
                        {
                            breakpoint: 650,
                            settings: {
                                slidesToShow: 4,
                            }
                        },
                        {
                            breakpoint: 849,
                            settings: {
                                slidesToShow: 6,
                            }
                        },
                    ]
                });

                $('.slider-for .item').magnificPopup({
                    delegate: 'a',
                    type: 'image',
                    tLoading: 'Loading image #%curr%...',
                    mainClass: 'mfp-img-mobile',
                    gallery: {
                        enabled: true,
                        navigateByImgClick: true,
                        preload: [0, 1] // Will preload 0 - before current, and 1 after the current image
                    },
                    image: {
                        tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
                        // titleSrc: function(item) {
                        // 	return item.el.attr('title') + '<small>by Marsel Van Oosten</small>';
                        // }
                    }
                });
            });
        </script>
    </div>
    <!-- </div> -->
<?php } ?>
<?php
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'change_variant_gallery') {
    wp_reset_postdata();
}
