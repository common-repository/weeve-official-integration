<style>
    .discounts_tables {
        padding: 20px;
    }

    .discounts_tables .head_discounts, .item-discounts {
        display: flex;
        flex-wrap: nowrap;
    }

    .discounts_tables .item-discounts div, .head_discounts div {
        width: 350px;
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: left;
    }

    .discounts_tables div.special-table {
        width: 350px;
    }

    .discounts_tables input {
        width: 100% !important;
    }

    .discounts_tables input {
        height: 10px;
    }

    .discounts_tables div.number_element {
        width: 20px;
    }

    .head_discounts {
        margin-bottom: 10px;
    }
    .title-discounts {
        font-weight: bold;
        margin-bottom: 20px;
        text-transform: uppercase;
    }

    .discounts_tables {
        border-bottom: 1px solid #ddd;
    }
    .item-discounts {
        margin-bottom: 15px;
    }
    .delete_item {
        color: red;
        cursor: pointer;
    }
    #save-settings {
        margin: 20px;
        padding: 5px 50px;
    }
    #discounts_tab_content {background: #fff;margin-top: 30px;margin-right: 20px;}
</style>

<form action="" method="post">
    <div id="discounts_tab_content" class="panel woocommerce_options_panel">
        <div class="discounts_tables">
            <div class="title-discounts">Levels by points Amount</div>
            <div class="head_discounts">
                <div class="number_element">#</div>
                <div class="item-table">Level Name (Rank)</div>
                <div class="item-table">Minimum points total to get rank</div>
            </div>
            <div class="body_discounts more-discount">
                <?php if(isset($settings['rank']) && count($settings['rank']) > 0) { $i = 1; foreach ($settings['rank'] as $item => $rank) { ?>
                    <div class="item-discounts">
                        <div class="number_element"><?php echo $i; ?></div>
                        <div class="item-table"><input type="text" name="wpm_points_settings[rank][]" value="<?php echo esc_html($rank); ?>"></div>
                        <div class="item-table"><input type="number" name="wpm_points_settings[points][]" value="<?php echo esc_html($settings['points'][$item]); ?>"></div>
                        <?php if($item >= 1) {
                            echo '<div class="delete_item">Delete</div>';
                        } ?>
                    </div>
                    <?php $i++; }} else { ?>
                    <div class="item-discounts">
                        <div class="number_element">1</div>
                        <div class="item-table"><input type="text" name="wpm_points_settings[rank][]"></div>
                        <div class="item-table"><input type="number" name="wpm_points_settings[points][]"></div>
                    </div>
                <?php } ?>
                <button class="button button-primary button-large" type="button" id="add-discount">Add new level discount</button>
            </div>
        </div>
        <div class="discounts_tables special">
            <div class="title-discounts">How Much Points Give by Price</div>
            <div class="head_discounts">
                <div class="item-table">Points in % (100 / 10%) = 10 pts</div>
                <div class="item-table" style="display: none">Price in points to get discount</div>
                <div class="item-table" style="display: none">Latest days number to calculate rank</div>
            </div>
            <div class="body_discounts">
                <div class="item-discounts">
                    <div class="special-table"><input type="number" name="wpm_points_settings[percent]" value="<?php if(isset($settings['percent'])) {echo esc_html($settings['percent']);} ?>"></div>
                    <div class="special-table" style="display: none"><input type="number" name="wpm_points_settings[price]" value="<?php if(isset($settings['price'])) {echo esc_html($settings['price']);} else {echo 100;} ?>"></div>
                    <div class="special-table" style="display: none"><input type="number" name="wpm_points_settings[expire]" value="<?php if(isset($settings['expire'])) {echo esc_html($settings['expire']);} else {echo 360;} ?>"></div>
                </div>
            </div>
        </div>
        <div class="discounts_tables special">
            <div class="title-discounts">Weeve API Keys</div>
            <div class="head_discounts">
                <div class="item-table">API Key</div>
                <div class="item-table">API Secret</div>
            </div>
            <div class="body_discounts">
                <div class="item-discounts">
                    <div class="special-table"><input type="text" name="wpm_points_settings[api_key]" value="<?php if(isset($settings['api_key'])) {echo esc_html($settings['api_key']);} ?>"></div>
                    <div class="special-table"><input type="text" name="wpm_points_settings[api_secret]" value="<?php if(isset($settings['api_secret'])) {echo esc_html($settings['api_secret']);} ?>"></div>
                </div>
            </div>
        </div>
        <button class="button button-primary button-large" id="save-settings" type="submit">Save settings</button>
    </div>
</form>

<script>
    jQuery('body').on('click', '#add-discount', function(){
        var count = jQuery('.more-discount .item-discounts').length;

        jQuery("#add-discount").before(jQuery('.more-discount .item-discounts:last').clone());
        jQuery('.more-discount .item-discounts:last').find('input').val('');
        var counter = parseInt(jQuery('.more-discount .item-discounts:last').find('.number_element').text());

        counter++;
        jQuery('.more-discount .item-discounts:last').find('.number_element').text(counter);

        if(count == 1) {
            jQuery('.more-discount .item-discounts:last').append('<div class="delete_item">Delete</div>');
        }
    });

    jQuery("body").on("click",".delete_item",function(){
        jQuery(this).closest('.item-discounts').remove();
    });
</script>