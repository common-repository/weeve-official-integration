<style>
    .discounts_tables {
        padding: 20px;
    }

    .discounts_tables .head_discounts, .item-discounts {
        display: flex;
        flex-wrap: nowrap;
    }

    .discounts_tables .item-discounts div, .head_discounts div {
        width: 200px;
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: left;
    }

    .discounts_tables div.special-table, select {
        width: 200px;
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
            <div class="title-discounts">Rewards Vouchers</div>
            <div class="head_discounts">
                <div class="number_element">#</div>
                <div class="item-table">Name</div>
                <div class="item-table">Voucher (Code)</div>
                <div class="item-table">Must Have Pts.</div>
                <div class="item-table">Gift points</div>
                <div class="item-table">Status</div>
            </div>
            <div class="body_discounts more-voucher">
                <?php if(isset($vouchers_ranks['code']) && count($vouchers_ranks['code']) > 0) { $i = 1; foreach ($vouchers_ranks['code'] as $item => $voucher) { ?>
                    <div class="item-discounts">
                        <div class="number_element"><?php echo $i; ?></div>
                        <div class="item-table"><input type="text" name="rewards_vouchers[name][]" value="<?php echo esc_html($vouchers_ranks['name'][$item]); ?>"></div>
                        <div class="item-table"><input type="text" name="rewards_vouchers[code][]" value="<?php echo esc_html($voucher); ?>"></div>
                        <div class="item-table"><input type="number" name="rewards_vouchers[need][]" value="<?php echo esc_html($vouchers_ranks['need'][$item]); ?>"></div>
                        <div class="item-table"><input type="number" name="rewards_vouchers[points][]" value="<?php echo esc_html($vouchers_ranks['points'][$item]); ?>"></div>
                        <div class="item-table">
                            <select name="rewards_vouchers[status][]">
                                <option value="1" <?php if(isset($vouchers_ranks['status']) && $vouchers_ranks['status'][$item] == 1) {echo 'selected';} ?>>Active</option>
                                <option value="0" <?php if(isset($vouchers_ranks['status']) && $vouchers_ranks['status'][$item] == 0) {echo 'selected';} ?>>Disabled</option>
                            </select>
                        </div>
                        <?php if($item >= 1) {
                            echo '<div class="delete_item">Delete</div>';
                        } ?>
                    </div>
                    <?php $i++; }} else { ?>
                    <div class="item-discounts">
                        <div class="number_element">1</div>
                        <div class="item-table"><input type="text" name="rewards_vouchers[name][]" value=""></div>
                        <div class="item-table"><input type="text" name="rewards_vouchers[code][]" value=""></div>
                        <div class="item-table"><input type="number" name="rewards_vouchers[need][]" value=""></div>
                        <div class="item-table"><input type="number" name="rewards_vouchers[points][]" value=""></div>
                        <div class="item-table">
                            <select name="rewards_vouchers[status][]">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </div>
                <?php } ?>
                <button class="button button-primary button-large" type="button" id="add-voucher">Add new reward</button>
            </div>
        </div>
        <button class="button button-primary button-large" id="save-settings" type="submit">Save settings</button>
    </div>
</form>

<script>
    jQuery('body').on('click', '#add-discount', function() {
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

    jQuery('body').on('click', '#add-voucher', function(){
        var count = jQuery('.more-voucher .item-discounts').length;

        jQuery("#add-voucher").before(jQuery('.more-voucher .item-discounts:last').clone());
        jQuery('.more-voucher .item-discounts:last').find('input').val('');
        var counter = parseInt(jQuery('.more-voucher .item-discounts:last').find('.number_element').text());

        counter++;
        jQuery('.more-voucher .item-discounts:last').find('.number_element').text(counter);

        if(count == 1) {
            jQuery('.more-voucher .item-discounts:last').append('<div class="delete_item">Delete</div>');
        }
    });

    jQuery("body").on("click",".delete_item",function(){
        jQuery(this).closest('.item-discounts').remove();
    });
</script>