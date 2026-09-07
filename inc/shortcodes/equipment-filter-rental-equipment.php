<?php
/**
 * [gti_rental_equipment_filter] Shortcode — Rental Equipment Catalog with Filter Panel
 *
 * Usage:
 *   [gti_rental_equipment_filter]
 *   [gti_rental_equipment_filter per_page="12"]
 *
 * Queries gti_equipment WHERE type='rental'.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'gti_is_price_valid' ) ) {
    function gti_is_price_valid( $price_valid_until ) {
        if ( empty( $price_valid_until ) ) {
            return true;
        }
        $valid_until = strtotime( $price_valid_until );
        $today = strtotime( current_time( 'Y-m-d' ) );
        return $valid_until >= $today;
    }
}

add_shortcode( 'gti_rental_equipment_filter', 'gti_render_rental_equipment_filter' );

function gti_render_rental_equipment_filter( $atts = [] ) {
    $defaults = [
        'per_page' => 12,
    ];

    $atts = shortcode_atts( $defaults, $atts, 'gti_rental_equipment_filter' );
    $per_page = absint( $atts['per_page'] );

    // Detail Page Mode
    $equip_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( $equip_id > 0 ) {
        return gti_rental_render_equipment_detail( $equip_id );
    }

    // Get rental equipment data only
    $equipment_data = gti_get_rental_equipment_data();
    $categories     = gti_get_rental_equipment_categories( $equipment_data );
    $brands         = gti_get_rental_equipment_brands( $equipment_data );
    $locations      = gti_get_rental_equipment_locations( $equipment_data );
    $conditions     = gti_get_rental_equipment_conditions( $equipment_data );

    ob_start();
    ?>
    <div class="gti-ef-wrapper" id="gti-equipment-filter"
         data-type="rental"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'gti_rental_equipment_filter' ) ); ?>"
         data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

        <!-- Mobile Filter Toggle -->
        <button class="gti-ef-mobile-filter-toggle" id="gti-ef-mobile-toggle" type="button">
            <i class="fas fa-sliders-h"></i>
            <span>Filters</span>
        </button>

        <!-- Mobile Overlay -->
        <div class="gti-ef-mobile-overlay" id="gti-ef-mobile-overlay"></div>

        <div class="gti-ef-container">
            <!-- ═══ SIDEBAR FILTER PANEL ═══ -->
            <aside class="gti-ef-sidebar" id="gti-ef-sidebar">
                <div class="gti-ef-filters">

                    <!-- CATEGORIES -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header open" data-filter="category">
                            <h4>CATEGORIES</h4>
                            <span class="toggle-icon"><i class="fas fa-minus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body open" data-filter-body="category">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $categories as $cat ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-category" value="<?php echo esc_attr( $cat['value'] ); ?>">
                                        <span><i class="<?php echo esc_attr( $cat['icon'] ); ?>"></i> <?php echo esc_html( $cat['name'] ); ?> (<?php echo esc_html( $cat['count'] ); ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BRAND -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="brand">
                            <h4>BRAND</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="brand">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $brands as $brand ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-brand" value="<?php echo esc_attr( $brand ); ?>">
                                        <span><?php echo esc_html( $brand ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- YEAR -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="year">
                            <h4>YEAR</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="year">
                            <div class="gti-ef-range-inputs">
                                <input type="number" name="ef-year-min" placeholder="Min" min="1990" max="2099">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="number" name="ef-year-max" placeholder="Max" min="1990" max="2099">
                            </div>
                        </div>
                    </div>

                    <!-- PRICE RANGE (Monthly Rental) -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="price">
                            <h4>PRICE RANGE</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="price">
                            <div class="gti-ef-range-inputs">
                                <input type="text" name="ef-price-min" placeholder="Min (Rp/month)">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="text" name="ef-price-max" placeholder="Max (Rp/month)">
                            </div>
                        </div>
                    </div>

                    <!-- HOUR METER -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="hours">
                            <h4>HOUR METER</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="hours">
                            <div class="gti-ef-range-inputs">
                                <input type="number" name="ef-hours-min" placeholder="Min hrs" min="0">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="number" name="ef-hours-max" placeholder="Max hrs" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- LOCATION -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="location">
                            <h4>LOCATION</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="location">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $locations as $loc ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-location" value="<?php echo esc_attr( $loc ); ?>">
                                        <span><?php echo esc_html( $loc ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CONDITION -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="condition">
                            <h4>CONDITION</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="condition">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $conditions as $cond ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-condition" value="<?php echo esc_attr( $cond ); ?>">
                                        <span><?php echo esc_html( $cond ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="gti-ef-actions">
                    <button type="button" class="gti-ef-btn-reset" id="gti-ef-reset">
                        <i class="fas fa-redo-alt"></i>
                        <span>RESET FILTER</span>
                    </button>
                    <button type="button" class="gti-ef-btn-apply" id="gti-ef-apply">
                        <i class="fas fa-filter"></i>
                        <span>APPLY FILTER</span>
                    </button>
                </div>
            </aside>

            <!-- ═══ MAIN CONTENT ═══ -->
            <div class="gti-ef-content">
                <!-- Top Bar -->
                <div class="gti-ef-topbar">
                    <div class="gti-ef-result-count" id="gti-ef-result-count">
                        Showing <strong>1 - <?php echo esc_html( $per_page ); ?></strong> of <strong><?php echo esc_html( count( $equipment_data ) ); ?></strong> units
                    </div>
                    <div class="gti-ef-topbar-right">
                        <div class="gti-ef-sort-wrapper">
                            <span class="gti-ef-sort-label">Sort by:</span>
                            <select class="gti-ef-sort-select" id="gti-ef-sort">
                                <option value="newest">Newest First</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="hours-low">Hours: Low to High</option>
                            </select>
                        </div>
                        <div class="gti-ef-view-switcher">
                            <button class="gti-ef-view-btn active" data-view="grid" title="Grid View"><i class="fas fa-th"></i></button>
                            <button class="gti-ef-view-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Card Grid -->
                <div class="gti-ef-grid" id="gti-ef-grid">
                    <?php foreach ( $equipment_data as $item ) : ?>
                        <?php gti_rental_render_equipment_card( $item ); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Loading Skeleton -->
                <div class="gti-ef-loading" id="gti-ef-loading">
                    <div class="gti-ef-grid">
                        <?php for ( $i = 0; $i < 6; $i++ ) : ?>
                            <div class="gti-ef-skeleton-card">
                                <div class="gti-ef-skeleton-image"></div>
                                <div class="gti-ef-skeleton-body">
                                    <div class="gti-ef-skeleton-line" style="width:80%"></div>
                                    <div class="gti-ef-skeleton-line" style="width:60%"></div>
                                    <div class="gti-ef-skeleton-line" style="width:100%"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- No Results -->
                <div class="gti-ef-no-results" id="gti-ef-no-results" style="display:none;">
                    <i class="fas fa-search"></i>
                    <h3>No rental equipment found</h3>
                    <p>Try adjusting your filters or search criteria to find what you're looking for.</p>
                </div>

                <!-- Pagination -->
                <div class="gti-ef-pagination" id="gti-ef-pagination">
                    <?php gti_rental_render_pagination( count( $equipment_data ), $per_page ); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ═══════════════════════════════════════════════════════════════════════════
// CARD, PAGINATION, DATA, FILTER HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function gti_rental_render_equipment_card( $item ) {
    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon': case 'coming-soon':
                $status_class = 'coming-soon'; $status_label = 'COMING SOON'; break;
            case 'sold':
                $status_class = 'sold'; $status_label = 'SOLD'; break;
            default:
                $status_class = 'ready'; $status_label = 'READY STOCK';
        }
    }

    $title     = isset( $item['title'] ) ? $item['title'] : 'Equipment';
    $year      = isset( $item['year'] ) ? $item['year'] : '—';
    $hours     = isset( $item['hours'] ) ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $location  = isset( $item['location'] ) ? $item['location'] : '—';
    $image_url = isset( $item['image'] ) ? $item['image'] : '';
    $price     = isset( $item['price'] ) ? $item['price'] : '';
    $id        = isset( $item['id'] ) ? $item['id'] : 0;
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'id', $id ) ); ?>" class="gti-ef-card"
         data-id="<?php echo esc_attr( $id ); ?>"
         data-category="<?php echo esc_attr( isset( $item['category'] ) ? $item['category'] : '' ); ?>"
         data-brand="<?php echo esc_attr( isset( $item['brand'] ) ? $item['brand'] : '' ); ?>"
         data-type="<?php echo esc_attr( isset( $item['type'] ) ? $item['type'] : '' ); ?>"
         data-year="<?php echo esc_attr( $year ); ?>"
         data-price="<?php echo esc_attr( $price ); ?>"
         data-hours="<?php echo esc_attr( isset( $item['hours'] ) ? $item['hours'] : '' ); ?>"
         data-location="<?php echo esc_attr( $location ); ?>"
         data-condition="<?php echo esc_attr( isset( $item['condition'] ) ? $item['condition'] : '' ); ?>">
        <div class="gti-ef-card-image">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="placeholder-icon"><i class="fas fa-truck-monster"></i></div>
            <?php endif; ?>
            <span class="gti-ef-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
        </div>
        <div class="gti-ef-card-body">
            <h3 class="gti-ef-card-title"><?php echo esc_html( $title ); ?></h3>
            <div class="gti-ef-card-meta">
                <div class="gti-ef-card-meta-row"><i class="fas fa-calendar-alt"></i><span><?php echo esc_html( $year ); ?></span></div>
                <div class="gti-ef-card-meta-row"><i class="fas fa-tachometer-alt"></i><span><?php echo esc_html( $hours ); ?></span></div>
                <div class="gti-ef-card-meta-row"><i class="fas fa-map-marker-alt"></i><span><?php echo esc_html( $location ); ?></span></div>
            </div>
            <span class="gti-ef-card-cta">DAPATKAN PENAWARAN SPESIAL <i class="fas fa-arrow-right"></i></span>
        </div>
    </a>
    <?php
}

function gti_rental_render_pagination( $total, $per_page ) {
    $total_pages = max( 1, ceil( $total / $per_page ) );
    $current     = 1;

    echo '<button class="gti-ef-page-btn" data-page="prev" disabled><i class="fas fa-chevron-left"></i></button>';

    $max_visible = 5;
    $start = max( 1, $current - floor( $max_visible / 2 ) );
    $end   = min( $total_pages, $start + $max_visible - 1 );
    if ( $end - $start < $max_visible - 1 ) {
        $start = max( 1, $end - $max_visible + 1 );
    }

    if ( $start > 1 ) {
        echo '<button class="gti-ef-page-btn" data-page="1">1</button>';
        if ( $start > 2 ) {
            echo '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
    }

    for ( $i = $start; $i <= $end; $i++ ) {
        $active = $i === $current ? ' active' : '';
        echo '<button class="gti-ef-page-btn' . $active . '" data-page="' . $i . '">' . $i . '</button>';
    }

    if ( $end < $total_pages ) {
        if ( $end < $total_pages - 1 ) {
            echo '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
        echo '<button class="gti-ef-page-btn" data-page="' . $total_pages . '">' . $total_pages . '</button>';
    }

    $next_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="next"' . $next_disabled . '><i class="fas fa-chevron-right"></i></button>';

    $last_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="last"' . $last_disabled . '><i class="fas fa-angle-double-right"></i></button>';
}

// ═══════════════════════════════════════════════════════════════════════════
// DATA FUNCTIONS — rental equipment only
// ═══════════════════════════════════════════════════════════════════════════

function gti_get_rental_equipment_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $table_exists ) {
        return [];
    }

    $rows = $wpdb->get_results(
        "SELECT * FROM {$table} WHERE type = 'rental' AND deleted_at IS NULL AND status != 'draft' ORDER BY created_at DESC"
    );

    if ( empty( $rows ) ) {
        return gti_get_dummy_rental_equipment_data();
    }

    $items = [];
    foreach ( $rows as $row ) {
        if ( ! gti_is_price_valid( $row->price_valid_until ) ) {
            continue;
        }

        $image = '';
        if ( ! empty( $row->main_image ) ) {
            $image = $row->main_image;
        } elseif ( ! empty( $row->images ) ) {
            $imgs = json_decode( $row->images, true );
            if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                $image = $imgs[0];
            }
        }

        $status = $row->status ?: 'available';
        switch ( strtolower( $status ) ) {
            case 'sold':        $display_status = 'sold'; break;
            case 'coming_soon': $display_status = 'coming_soon'; break;
            default:            $display_status = 'ready_stock';
        }

        $items[] = [
            'id'            => (int) $row->id,
            'title'         => $row->name ?: 'Equipment',
            'url'           => '#',
            'category'      => strtolower( $row->category ?: 'others' ),
            'brand'         => $row->brand ?: '',
            'type'          => $row->category ?: '',
            'year'          => $row->year ?: '',
            'price'         => $row->price ?: '',
            'hours'         => $row->hours ?: '',
            'location'      => $row->location ?: '',
            'condition'     => $row->condition_status ?: '',
            'status'        => $display_status,
            'image'         => $image,
            'is_wishlisted' => false,
        ];
    }

    return $items;
}

function gti_get_dummy_rental_equipment_data() {
    if ( ! function_exists( 'gti_get_dummy_equipment_data' ) ) {
        return [];
    }
    $all = gti_get_dummy_equipment_data();
    return array_values( array_filter( $all, function( $item ) {
        return ( $item['type'] ?? '' ) === 'rental';
    } ) );
}

function gti_get_rental_equipment_categories( $data = [] ) {
    return gti_catalog_category_options( $data, gti_equipment_categories() );
}

function gti_get_rental_equipment_brands( $data = [] ) {
    $brands = array_unique( array_filter( array_column( $data, 'brand' ) ) );
    if ( empty( $brands ) ) {
        $brands = [ 'CAT', 'Komatsu', 'Hitachi', 'Volvo', 'Doosan', 'Hino', 'Kato', 'SANY', 'SDLG', 'XCMG', 'Kobelco', 'Hyundai' ];
    }
    sort( $brands );
    return $brands;
}

function gti_get_rental_equipment_locations( $data = [] ) {
    $locs = array_unique( array_filter( array_column( $data, 'location' ) ) );
    if ( empty( $locs ) ) {
        $locs = [ 'Balikpapan', 'Samarinda', 'Banjarmasin', 'Palangkaraya', 'Pontianak', 'Banjarbaru' ];
    }
    sort( $locs );
    return $locs;
}

function gti_get_rental_equipment_conditions( $data = [] ) {
    $conds = array_unique( array_filter( array_column( $data, 'condition' ) ) );
    if ( empty( $conds ) ) {
        $conds = [ 'Excellent', 'Good', 'Fair', 'Poor' ];
    }
    $conds = array_map( function( $c ) { return ucfirst( strtolower( $c ) ); }, $conds );
    $conds = array_unique( $conds );
    sort( $conds );
    return $conds;
}

// ═══════════════════════════════════════════════════════════════════════════
// DETAIL PAGE
// ═══════════════════════════════════════════════════════════════════════════

function gti_rental_render_equipment_detail( $equipment_id ) {
    $item = gti_rental_get_equipment_by_id( $equipment_id );
    if ( ! $item ) {
        return '<div class="gti-ed-wrapper"><div style="text-align:center;padding:80px 20px;"><h2>Equipment not found.</h2><p><a href="' . esc_url( remove_query_arg( 'id' ) ) . '">&larr; Back to catalog</a></p></div></div>';
    }

    $brand          = strtoupper( $item['brand'] ?: '' );
    $model          = $item['model'] ?: '';
    $title          = $item['title'] ?: 'Equipment';
    $year           = $item['year'] ?: '—';
    $hours          = $item['hours'] ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $condition      = $item['condition'] ?: '—';
    $location       = $item['location'] ?: '—';
    $price          = $item['price'] ?: '';
    $image          = $item['image'] ?: '';
    $category       = $item['category'] ?: '';
    $description    = $item['description'] ?: '';
    $equipment_code = $item['equipment_code'] ?: '';

    // Helper functions
    $fmt_rupiah = function( $val ) {
        if ( ! $val ) return '—';
        return 'Rp ' . number_format( (float) $val, 0, ',', '.' );
    };
    $fmt_date = function( $dateStr ) {
        if ( ! $dateStr ) return '—';
        $d = new DateTime( $dateStr );
        return $d->format( 'd M Y' );
    };

    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon': case 'coming-soon':
                $status_class = 'coming-soon'; $status_label = 'COMING SOON'; break;
            case 'sold':
                $status_class = 'sold'; $status_label = 'SOLD'; break;
            default:
                $status_class = 'ready'; $status_label = 'READY STOCK';
        }
    }

    $gallery_images = [];
    if ( ! empty( $item['image'] ) ) {
        $gallery_images[] = $item['image'];
    }
    if ( ! empty( $item['images'] ) ) {
        $parsed_imgs = json_decode( $item['images'], true );
        if ( is_array( $parsed_imgs ) ) {
            $gallery_images = array_merge( $gallery_images, $parsed_imgs );
        }
    }
    $gallery_images = array_unique( $gallery_images );
    $max_thumbs = 5;
    $display_gallery = array_slice( $gallery_images, 0, $max_thumbs );

    $features = [
        'Good Undercarriage',
        'Strong Engine Performance',
        'All Functions Normal',
        'Ready to Work',
        'Regularly Serviced',
    ];
    if ( $condition && $condition !== '—' ) {
        array_unshift( $features, $condition . ' Condition' );
    }

    $related = gti_rental_get_related_equipment( $item, 8 );
    $wa_number = get_option( 'gti_whatsapp_number', '6281234567890' );

    ob_start();
    ?>
    <div class="gti-ed-wrapper" id="gti-equipment-detail"
         data-id="<?php echo esc_attr( $equipment_id ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-model="<?php echo esc_attr( $model ); ?>"
         data-name="<?php echo esc_attr( $title ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'gti_customer_quotation' ) ); ?>"
         data-wa="<?php echo esc_attr( $wa_number ); ?>">

        <nav class="gti-ed-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo esc_url( remove_query_arg( 'id' ) ); ?>">Rental Equipment</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="current"><?php echo esc_html( $title ); ?></span>
        </nav>

        <!-- ═══ HERO ═══ -->
        <section class="gti-ed-hero">
            <div class="gti-ed-hero-grid">
                <div class="gti-ed-gallery">
                    <div class="gti-ed-main-image-wrapper">
                        <?php if ( $image ) : ?>
                            <img id="gti-ed-main-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                        <?php else : ?>
                            <div class="placeholder-icon"><i class="fas fa-truck-monster"></i></div>
                        <?php endif; ?>
                        <span class="gti-ed-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
                        <button class="gti-ed-gallery-arrow prev" type="button" aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
                        <button class="gti-ed-gallery-arrow next" type="button" aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="gti-ed-thumbnails">
                        <?php if ( ! empty( $display_gallery ) ) : ?>
                            <?php $visible_count = min( 4, count( $display_gallery ) ); ?>
                            <?php for ( $idx = 0; $idx < $visible_count; $idx++ ) : ?>
                                <?php $img = $display_gallery[ $idx ]; ?>
                                <div class="gti-ed-thumb <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $idx ); ?>" data-src="<?php echo esc_url( $img ); ?>">
                                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title . ' photo ' . ( $idx + 1 ) ); ?>">
                                </div>
                            <?php endfor; ?>
                            <?php if ( count( $display_gallery ) > 4 ) : ?>
                                <?php $extra = count( $display_gallery ) - 4; ?>
                                <div class="gti-ed-thumb gti-ed-thumb-more">
                                    <img src="<?php echo esc_url( $display_gallery[4] ?? $display_gallery[0] ); ?>" alt="More photos">
                                    <span class="gti-ed-thumb-overlay">+<?php echo esc_html( $extra ); ?> More</span>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php for ( $idx = 0; $idx < 5; $idx++ ) : ?>
                                <div class="gti-ed-thumb <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $idx ); ?>">
                                    <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="gti-ed-summary">
                    <?php if ( $brand ) : ?>
                        <span class="gti-ed-brand-badge"><?php echo esc_html( $brand ); ?></span>
                    <?php endif; ?>
                    <h1 class="gti-ed-title"><?php echo esc_html( $title ); ?></h1>
                    <p class="gti-ed-tagline">DAPATKAN PENAWARAN SPESIAL</p>

                    <div class="gti-ed-specs-list">
                        <div class="gti-ed-spec-item"><i class="fas fa-calendar-alt"></i><span class="spec-label">Year</span><span class="spec-value"><?php echo esc_html( $year ); ?></span></div>
                        <div class="gti-ed-spec-item"><i class="fas fa-tachometer-alt"></i><span class="spec-label">Working Hour</span><span class="spec-value"><?php echo esc_html( $hours ); ?></span></div>
                        <div class="gti-ed-spec-item"><i class="fas fa-check-circle"></i><span class="spec-label">Condition</span><span class="spec-value"><?php echo esc_html( $condition ); ?></span></div>
                        <div class="gti-ed-spec-item"><i class="fas fa-map-marker-alt"></i><span class="spec-label">Location</span><span class="spec-value"><?php echo esc_html( $location ); ?>, Indonesia</span></div>
                    </div>

                    <div class="gti-ed-cta-group">
                        <a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>?text=<?php echo urlencode( 'Halo GTI, saya tertarik dengan unit rental ' . $title ); ?>" target="_blank" rel="noopener noreferrer" class="gti-ed-btn gti-ed-btn-whatsapp"><i class="fab fa-whatsapp"></i> CHAT ON WHATSAPP</a>
                        <button type="button" class="gti-ed-btn gti-ed-btn-offer" onclick="document.getElementById('gti-ed-inquiry-form').scrollIntoView({behavior:'smooth'});var f=document.getElementById('ed-name');if(f)f.focus();"><i class="fas fa-file-invoice-dollar"></i> REQUEST QUOTATION</button>
                        <button type="button" id="gti-ed-share-btn" class="gti-ed-btn gti-ed-btn-share"><i class="fas fa-share-alt"></i> <span>SHARE UNIT</span></button>
                    </div>
                </div>

                <div class="gti-ed-inquiry-card">
                    <h3 class="gti-ed-inquiry-title">INTERESTED IN THIS UNIT?</h3>
                    <p class="gti-ed-inquiry-desc">Fill out the form and our team will contact you.</p>
                    <form id="gti-ed-inquiry-form">
                        <input type="hidden" name="gti_quot_nonce" value="<?php echo esc_attr( wp_create_nonce( 'gti_customer_quotation' ) ); ?>">
                        <div class="gti-ed-form-group"><input type="text" name="ed_name" id="ed-name" placeholder="Your Name" required></div>
                        <div class="gti-ed-form-group"><input type="text" name="ed_company" id="ed-company" placeholder="Your Company"></div>
                        <div class="gti-ed-form-group"><input type="tel" name="ed_phone" id="ed-phone" placeholder="Phone / WhatsApp" required></div>
                        <div class="gti-ed-form-group"><input type="email" name="ed_email" id="ed-email" placeholder="Your Email" required></div>
                        <div class="gti-ed-form-group"><textarea name="ed_message" id="ed-message" rows="3" placeholder="Your Message"></textarea></div>
                        <button type="submit" class="gti-ed-form-submit"><i class="fas fa-paper-plane"></i> SEND MESSAGE</button>
                        <div class="gti-ed-form-privacy"><i class="fas fa-lock"></i> Your data is safe with us.</div>
                    </form>
                </div>
            </div>
        </section>

        <!-- ═══ QUICK INFO (3 COLUMNS) ═══ -->
        <section class="gti-ed-detail-section" style="margin-bottom: 32px;">
            <style>
                .gti-ed-quick-grid { display: grid; grid-template-columns: 1.8fr 0.8fr; gap: 32px; }
                .gti-ed-quick-block { border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; }

                /* Inline tabs styling */
                .gti-ed-quick-tabs { display: flex; gap: 4px; border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
                .gti-ed-quick-tabs h3 { font-size: 13px; font-weight: 600; color: #6b7280; margin: 0; padding: 6px 12px; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; transition: all 0.2s; border-bottom: 2px solid transparent; white-space: nowrap; position: relative; }
                .gti-ed-quick-tabs h3:hover { color: #1a1f36; }
                .gti-ed-quick-tabs h3.active { color: #F5A623; border-bottom: 2px solid #F5A623; }

                /* Specifications 2-column layout with aligned values */
                .gti-ed-specs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 35px; }
                .gti-ed-spec-item-quick { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
                .gti-ed-spec-item-quick:last-child { border-bottom: none; }
                .gti-ed-spec-label-quick { font-size: 12px; color: #6b7280; font-weight: 500; }
                .gti-ed-spec-value-quick { font-size: 13px; color: #1a1f36; font-weight: 600; text-align: right; }

                /* Features list - compact */
                .gti-ed-features-list { list-style: none; padding: 0; margin: 0; width: fit-content; display: flex; flex-direction: column; gap: 4px; }
                .gti-ed-feature-item-quick { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 13px; color: #374151; white-space: nowrap; }
                .gti-ed-feature-item-quick i { color: #F5A623; font-size: 14px; flex-shrink: 0; }

                /* Why Buy grid 2 columns x 3 rows */
                .gti-ed-why-items { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
                .gti-ed-why-item-quick { display: flex; flex-direction: column; align-items: center; text-align: center; }
                .gti-ed-why-icon-quick { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #FEF3E2; border-radius: 8px; margin-bottom: 8px; }
                .gti-ed-why-icon-quick i { font-size: 24px; color: #F5A623; }
                .gti-ed-why-text-quick { font-size: 12px; color: #374151; font-weight: 500; line-height: 1.4; }

                /* Tab content visibility */
                .gti-ed-quick-content { display: none; padding-top: 16px; margin-top: 16px; border-top: 1px solid #e5e7eb; }
                .gti-ed-quick-content.active { display: block; }

                .gti-ed-quick-subtitle {
                    font-size: 12px;
                    font-weight: 600;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                    margin: 20px 0 10px;
                }

                .gti-ed-quick-subtitle:first-child {
                    margin-top: 0;
                }

                .gti-ed-quick-description {
                    margin-top: 16px;
                }

                .gti-ed-quick-description .gti-ed-spec-value-quick {
                    display: block;
                    margin-top: 6px;
                }

                .gti-ed-spec-value-quick.text-msg {
                    text-align: left;
                }

                @media (max-width: 1200px) { .gti-ed-quick-grid { grid-template-columns: 1fr; gap: 24px; } }
                @media (max-width: 768px) {
                    .gti-ed-specs-grid { flex-direction: column; }
                    .gti-ed-why-items { grid-template-columns: repeat(2, 1fr); }
                    .gti-ed-quick-tabs { overflow-x: auto; }
                }
            </style>
            <div class="gti-ed-quick-grid">
                <!-- SPECIFICATIONS -->
                <div class="gti-ed-quick-block">
                    <div class="gti-ed-quick-tabs" id="gti-quick-tabs">
                        <h3 class="active" onclick="gtiQuickTabSwitch('info', event)">Info</h3>
                        <h3 onclick="gtiQuickTabSwitch('specs', event)">Specs</h3>
                        <h3 onclick="gtiQuickTabSwitch('pricing', event)">Pricing</h3>
                        <h3 onclick="gtiQuickTabSwitch('more', event)">More</h3>
                    </div>

                    <!-- Info Content -->
                    <div class="gti-ed-quick-content active" data-tab="info">

                        <!-- General Information -->
                        <h4 class="gti-ed-quick-subtitle">General Information</h4>

                        <div class="gti-ed-specs-grid">

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Name</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['title'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Equipment Code</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['equipment_code'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Category</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['category'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Brand</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['brand'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Model</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['model'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Year</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['year'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Hours</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['hours'] ?: '—' ); ?>
                                </span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Condition</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['condition'] ?: '—' ); ?></span>
                            </div>

                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Location</span>
                                <span class="gti-ed-spec-value-quick">
                                    <?php echo esc_html( $item['location'] ?: '—' ); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Specs Content -->
                    <div class="gti-ed-quick-content" data-tab="specs">
                        <h4 class="gti-ed-quick-subtitle">Technical Specifications</h4>
                        <div class="gti-ed-specs-grid">
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Brand</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['brand'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Model</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['model'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Year</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['year'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Hours</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['hours'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Condition</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['condition'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Category</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['category'] ?: '—' ); ?></span>
                            </div>
                        </div>

                        <h4 class="gti-ed-quick-subtitle">Features</h4>
                        <?php
                        $features = ['Ready to Rent', 'Well Maintained', 'Full Support'];
                        echo '<ul class="gti-ed-features-list">';
                        foreach ($features as $feat) {
                            echo '<li class="gti-ed-feature-item-quick"><i class="fas fa-check-circle"></i>' . esc_html($feat) . '</li>';
                        }
                        echo '</ul>';
                        ?>
                    </div>

                    <!-- Pricing Content -->
                    <div class="gti-ed-quick-content" data-tab="pricing">
                        <h4 class="gti-ed-quick-subtitle">Rental Pricing</h4>
                        <div class="gti-ed-specs-grid">
                            <?php if ( $item['price'] ) : ?>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Monthly Rate</span>
                                <span class="gti-ed-spec-value-quick">Rp <?php echo esc_html( number_format( (float) $item['price'], 0, ',', '.' ) ); ?>/mo</span>
                            </div>
                            <?php endif; ?>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Availability</span>
                                <span class="gti-ed-spec-value-quick" style="color: #10b981;">Available</span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Rental Terms</span>
                                <span class="gti-ed-spec-value-quick">Flexible</span>
                            </div>
                        </div>
                    </div>

                    <!-- More Content -->
                    <div class="gti-ed-quick-content" data-tab="more">
                        <h4 class="gti-ed-quick-subtitle">Equipment Information</h4>
                        <div class="gti-ed-specs-grid">
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Equipment Code</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['equipment_code'] ?: '—' ); ?></span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Location</span>
                                <span class="gti-ed-spec-value-quick"><?php echo esc_html( $item['location'] ?: '—' ); ?></span>
                            </div>
                        </div>

                        <h4 class="gti-ed-quick-subtitle">Rental Support</h4>
                        <div class="gti-ed-specs-grid">
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Maintenance</span>
                                <span class="gti-ed-spec-value-quick">Included</span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Support</span>
                                <span class="gti-ed-spec-value-quick">24/7</span>
                            </div>
                            <div class="gti-ed-spec-item-quick">
                                <span class="gti-ed-spec-label-quick">Delivery</span>
                                <span class="gti-ed-spec-value-quick">Available</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FEATURES Column -->
                <div class="gti-ed-quick-side">
                    <div class="gti-ed-quick-block">
                        <h3 style="font-size: 16px; font-weight: 700; color: #1a1f36; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Features</h3>
                        <?php
                        $feature_items = [
                            'Ready to Rent' => true,
                            'Well Maintained' => true,
                            'Full Support Included' => true,
                            'Flexible Rental Terms' => true,
                            'Fast Delivery Available' => true,
                        ];

                        if ( $feature_items ) {
                            echo '<ul class="gti-ed-features-list">';
                            $count = 0;
                            foreach ( $feature_items as $label => $status ) {
                                if ( $status && $count < 5 ) {
                                    echo '<li class="gti-ed-feature-item-quick"><i class="fas fa-check-circle"></i>' . esc_html( $label ) . '</li>';
                                    $count++;
                                }
                            }
                            echo '</ul>';
                        }
                        ?>
                    </div>

                    <!-- WHY RENT FROM GTI Column -->
                    <div class="gti-ed-quick-block">
                        <h3 style="font-size: 16px; font-weight: 700; color: #1a1f36; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Why Rent From GTI?</h3>
                        <div class="gti-ed-why-items">
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-shield-alt"></i></div>
                                <div class="gti-ed-why-text-quick">All Units Inspected</div>
                            </div>
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-medal"></i></div>
                                <div class="gti-ed-why-text-quick">Quality Assurance</div>
                            </div>
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-box"></i></div>
                                <div class="gti-ed-why-text-quick">Flexible Rental Terms</div>
                            </div>
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-truck"></i></div>
                                <div class="gti-ed-why-text-quick">Nationwide Delivery</div>
                            </div>
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-headset"></i></div>
                                <div class="gti-ed-why-text-quick">Maintenance Included</div>
                            </div>
                            <div class="gti-ed-why-item-quick">
                                <div class="gti-ed-why-icon-quick"><i class="fas fa-tag"></i></div>
                                <div class="gti-ed-why-text-quick">Competitive Rates</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function gtiQuickTabSwitch(tab, e) {
                e.preventDefault();
                // Update tab buttons
                document.querySelectorAll('#gti-quick-tabs h3').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');

                // Update content visibility
                document.querySelectorAll('.gti-ed-quick-content').forEach(content => {
                    content.classList.remove('active');
                });
                const activeContent = document.querySelector('.gti-ed-quick-content[data-tab="' + tab + '"]');
                if (activeContent) activeContent.classList.add('active');
            }
            </script>
        </section>

        <!-- ═══ RELATED PRODUCTS ═══ -->
        <?php if ( ! empty( $related ) ) : ?>
        <section class="gti-ed-related-section">
            <div class="gti-ed-related-header">
                <h2>YOU MAY ALSO LIKE</h2>
                <a href="<?php echo esc_url( remove_query_arg( 'id' ) ); ?>">VIEW ALL &rarr;</a>
            </div>
            <div class="gti-ed-related-carousel-wrapper">
                <button class="gti-ed-carousel-arrow prev" type="button" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
                <div class="gti-ed-related-carousel" id="gti-ed-related-carousel">
                    <?php foreach ( $related as $rel ) : ?>
                        <?php gti_rental_render_related_card( $rel ); ?>
                    <?php endforeach; ?>
                </div>
                <button class="gti-ed-carousel-arrow next" type="button" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function gti_rental_render_related_card( $item ) {
    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon': case 'coming-soon':
                $status_class = 'coming-soon'; $status_label = 'COMING SOON'; break;
            case 'sold':
                $status_class = 'sold'; $status_label = 'SOLD'; break;
            default:
                $status_class = 'ready'; $status_label = 'READY STOCK';
        }
    }

    $title     = isset( $item['title'] ) ? $item['title'] : 'Equipment';
    $year      = isset( $item['year'] ) ? $item['year'] : '—';
    $hours     = isset( $item['hours'] ) ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $location  = isset( $item['location'] ) ? $item['location'] : '—';
    $image_url = isset( $item['image'] ) ? $item['image'] : '';
    $id        = isset( $item['id'] ) ? $item['id'] : 0;
    $price     = isset( $item['price'] ) ? $item['price'] : '';
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'id', $id ) ); ?>" class="gti-ed-related-card">
        <div class="gti-ed-related-card-image">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="placeholder-icon"><i class="fas fa-truck-monster"></i></div>
            <?php endif; ?>
            <span class="gti-ed-related-card-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
        </div>
        <div class="gti-ed-related-card-body">
            <h3 class="gti-ed-related-card-title"><?php echo esc_html( $title ); ?></h3>
            <div class="gti-ed-related-card-meta">
                <div class="gti-ed-related-card-meta-row"><i class="fas fa-calendar-alt"></i> <span><?php echo esc_html( $year ); ?></span></div>
                <div class="gti-ed-related-card-meta-row"><i class="fas fa-tachometer-alt"></i> <span><?php echo esc_html( $hours ); ?></span></div>
                <div class="gti-ed-related-card-meta-row"><i class="fas fa-map-marker-alt"></i> <span><?php echo esc_html( $location ); ?></span></div>
            </div>
            <?php if ( $price ) : ?>
            <div class="gti-ed-related-card-price">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?>/mo</div>
            <?php endif; ?>
            <span class="gti-ed-related-card-cta">DAPATKAN PENAWARAN SPESIAL</span>
        </div>
    </a>
    <?php
}

function gti_rental_map_complete_row( $row ) {
    $image = '';
    if ( ! empty( $row->main_image ) ) {
        $image = $row->main_image;
    } elseif ( ! empty( $row->images ) ) {
        $imgs = json_decode( $row->images, true );
        if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
            $image = $imgs[0];
        }
    }

    $status = $row->status ?: 'available';
    switch ( strtolower( $status ) ) {
        case 'sold':        $display_status = 'sold'; break;
        case 'coming_soon': case 'coming-soon': $display_status = 'coming_soon'; break;
        default:            $display_status = 'ready_stock';
    }

    return [
        'id'                      => (int) $row->id,
        'equipment_code'          => $row->equipment_code ?: '',
        'title'                   => $row->name ?: 'Equipment',
        'url'                     => '#',
        'category'                => strtolower( $row->category ?: 'others' ),
        'brand'                   => $row->brand ?: '',
        'model'                   => $row->model ?: '',
        'type'                    => $row->category ?: '',
        'year'                    => $row->year ?: '',
        'price'                   => $row->price ?: '',
        'hours'                   => $row->hours ?: '',
        'location'                => $row->location ?: '',
        'condition'               => $row->condition_status ?: '',
        'status'                  => $display_status,
        'image'                   => $image,
        'description'             => $row->description ?: '',
        'is_wishlisted'           => false,
        // Complete fields for detailed view
        'serial_number'           => isset( $row->serial_number ) ? $row->serial_number : '',
        'engine'                  => isset( $row->engine ) ? $row->engine : '',
        'engine_power'            => isset( $row->engine_power ) ? $row->engine_power : '',
        'origin_country'          => isset( $row->origin_country ) ? $row->origin_country : '',
        'operating_weight'        => isset( $row->operating_weight ) ? $row->operating_weight : '',
        'bucket_capacity'         => isset( $row->bucket_capacity ) ? $row->bucket_capacity : '',
        'stock_number'            => isset( $row->stock_number ) ? $row->stock_number : '',
        'rental_price'            => $row->price ?: '',
        'selling_price'           => isset( $row->selling_price ) ? $row->selling_price : '',
        'price_type'              => isset( $row->price_type ) ? $row->price_type : '',
        'vat_included'            => isset( $row->vat_included ) ? $row->vat_included : '',
        'currency'                => isset( $row->currency ) ? $row->currency : 'IDR',
        'price_valid_until'       => isset( $row->price_valid_until ) ? $row->price_valid_until : '',
        'negotiable'              => isset( $row->negotiable ) ? $row->negotiable : '',
        'availability_status'     => isset( $row->availability_status ) ? $row->availability_status : '',
        'ready_to_use'            => isset( $row->ready_to_use ) ? $row->ready_to_use : '',
        'service_history'         => isset( $row->service_history ) ? $row->service_history : '',
        'warranty_available'      => isset( $row->warranty_available ) ? $row->warranty_available : '',
        'warranty_period'         => isset( $row->warranty_period ) ? $row->warranty_period : '',
        'buyer_notes'             => isset( $row->buyer_notes ) ? $row->buyer_notes : '',
        'images'                  => isset( $row->images ) ? $row->images : '',
        'video_url'               => isset( $row->video_url ) ? $row->video_url : '',
        'video_file'              => isset( $row->video_file ) ? $row->video_file : '',
        'video_file_name'         => isset( $row->video_file_name ) ? $row->video_file_name : '',
        'specifications'          => isset( $row->specifications ) ? $row->specifications : '',
        'features'                => isset( $row->features ) ? $row->features : '',
        'previous_usage'          => isset( $row->previous_usage ) ? $row->previous_usage : '',
        'working_condition'       => isset( $row->working_condition ) ? $row->working_condition : '',
        'maintenance_record'      => isset( $row->maintenance_record ) ? $row->maintenance_record : '',
        'ownership'               => isset( $row->ownership ) ? $row->ownership : '',
        'operator_hours'          => isset( $row->operator_hours ) ? $row->operator_hours : '',
        'last_service_date'       => isset( $row->last_service_date ) ? $row->last_service_date : '',
        'equipment_history'       => isset( $row->equipment_history ) ? $row->equipment_history : '',
        'detailed_description'    => isset( $row->detailed_description ) ? $row->detailed_description : '',
        'documents'               => isset( $row->documents ) ? $row->documents : '',
        'location_country'        => isset( $row->location_country ) ? $row->location_country : '',
        'location_province'       => isset( $row->location_province ) ? $row->location_province : '',
        'location_city'           => isset( $row->location_city ) ? $row->location_city : '',
        'detailed_address'        => isset( $row->detailed_address ) ? $row->detailed_address : '',
        'map_location'            => isset( $row->map_location ) ? $row->map_location : '',
        'location_notes'          => isset( $row->location_notes ) ? $row->location_notes : '',
        'created_at'              => isset( $row->created_at ) ? $row->created_at : '',
        'updated_at'              => isset( $row->updated_at ) ? $row->updated_at : '',
        'notes'                   => isset( $row->notes ) ? $row->notes : '',
    ];
}

function gti_rental_get_equipment_by_id( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $table_exists ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND type = 'rental' AND deleted_at IS NULL AND status != 'draft'", $id ) );
        if ( $row ) {
            return gti_rental_map_complete_row( $row );
        }
    }

    // Fallback: dummy data
    $all_data = gti_get_dummy_rental_equipment_data();
    foreach ( $all_data as $idx => $item ) {
        $dummy_id = $idx + 1;
        if ( $dummy_id === (int) $id ) {
            $item['id'] = $dummy_id;
            return $item;
        }
    }

    return null;
}

function gti_rental_get_related_equipment( $current, $limit = 8 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';
    $items = [];

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $table_exists ) {
        $category   = $current['category'] ?: '';
        $current_id = $current['id'];

        if ( $category ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE type = 'rental' AND deleted_at IS NULL AND status != 'draft' AND LOWER(category) = LOWER(%s) AND id != %d ORDER BY created_at DESC LIMIT %d",
                    $category, $current_id, $limit
                )
            );
            foreach ( $rows as $row ) {
                $items[] = gti_rental_map_row( $row );
            }
        }

        if ( count( $items ) < $limit ) {
            $existing_ids = array_column( $items, 'id' );
            $existing_ids[] = $current['id'];
            $placeholders = implode( ',', array_fill( 0, count( $existing_ids ), '%d' ) );
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE type = 'rental' AND deleted_at IS NULL AND status != 'draft' AND id NOT IN ({$placeholders}) ORDER BY created_at DESC LIMIT %d",
                    array_merge( $existing_ids, [ $limit - count( $items ) ] )
                )
            );
            foreach ( $rows as $row ) {
                $items[] = gti_rental_map_row( $row );
            }
        }
    }

    if ( empty( $items ) ) {
        $all_data = gti_get_dummy_rental_equipment_data();
        $current_id = $current['id'] ?? 0;
        foreach ( $all_data as $idx => $d ) {
            $dummy_id = $idx + 1;
            if ( $dummy_id !== (int) $current_id ) {
                $d['id'] = $dummy_id;
                $items[] = $d;
                if ( count( $items ) >= $limit ) break;
            }
        }
    }

    return $items;
}

function gti_rental_map_row( $row ) {
    $image = '';
    if ( ! empty( $row->main_image ) ) {
        $image = $row->main_image;
    } elseif ( ! empty( $row->images ) ) {
        $imgs = json_decode( $row->images, true );
        if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
            $image = $imgs[0];
        }
    }

    $status = $row->status ?: 'available';
    switch ( strtolower( $status ) ) {
        case 'sold':        $display_status = 'sold'; break;
        case 'coming_soon': case 'coming-soon': $display_status = 'coming_soon'; break;
        default:            $display_status = 'ready_stock';
    }

    return [
        'id'       => (int) $row->id,
        'title'    => $row->name ?: 'Equipment',
        'category' => strtolower( $row->category ?: 'others' ),
        'brand'    => $row->brand ?: '',
        'model'    => $row->model ?: '',
        'year'     => $row->year ?: '',
        'price'    => $row->price ?: '',
        'hours'    => $row->hours ?: '',
        'location' => $row->location ?: '',
        'condition'=> $row->condition_status ?: '',
        'status'   => $display_status,
        'image'    => $image,
    ];
}
