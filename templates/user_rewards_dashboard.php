<div class="wpm-rewards-wrapper">
    <div class="wpm-earned-point">
        <div class="text-center" style="width:400px; margin:auto;">
			<div class="wpm-earned-title">You have</div>
			<div class="wpm-points-count"><?php echo esc_html(number_format($points, 2, '.', '')); ?>
			<br>Points</div>
			<div class="wpm-description-points"><?php echo esc_html($currency); ?>100 spent = <?php echo esc_html($get_points); ?> points earned</div>
		</div>
    </div>
    <div class="wpm-rewards-ranks" style="display: none">
        <div class="wpm-current-rank">
            <div class="wpm-rank-block">
                You're a <b><?php echo esc_html($user_rank['rank']) ?></b>
                <span><span>Account Holder</span>!</span>
            </div>
            <div class="wpm-rank-block-small">
                up to <b><?php echo esc_html($discount[$user_rank['rank_id']]) ?>%</b> rewards
            </div>
        </div>
        <?php if(isset($settings['rank'][$user_rank['rank_id']+1])) { ?>
        <div class="wpm-next-rank">
            <div class="wpm-rank-block">
                <p>Only another<br><?php echo esc_html($settings['points'][$user_rank['rank_id']+1] - $points); ?> points to go<br>to become a</p>
                <h6 class="white"><?php echo esc_html($settings['rank'][$user_rank['rank_id']+1]); ?></h6>
            </div>
            <div class="wpm-rank-block-small">
                up to <b><?php echo esc_html($discount[$user_rank['rank_id']+1]); ?>%</b> rewards
            </div>
        </div>
        <?php } ?>
        <?php if(isset($settings['rank'][$user_rank['rank_id']+2])) { ?>
        <div class="wpm-after-next-rank">
            <div class="wpm-rank-block">
                <p>... And a meagre<br><span><?php echo esc_html($settings['points'][$user_rank['rank_id']+2] - $points); ?></span> points<br>to get to</p>
                <h6 class="white"><?php echo esc_html($settings['rank'][$user_rank['rank_id']+2]); ?></h6>
            </div>
            <div class="wpm-rank-block-small">
                up to <b><?php echo esc_html($discount[$user_rank['rank_id']+2]); ?>%</b> rewards
            </div>
        </div>
        <?php } ?>
    </div>
    <div class="wpm-voucher-points" style="padding: 0px; display: none">
        <form action="" method="post">
            <input type="text" name="voucher" placeholder="Activate Voucher to get Reward Points">
            <button type="submit">Activate voucher</button>
        </form>
    </div>

    <?php if(isset($_GET['response'])) { ?>
        <div class="wpm-response-message"><?php echo esc_html($_GET['response']);?></div>
    <?php } ?>
    <?php if(isset($rewards) && count($rewards['code']) > 0) { ?>
        <h2 class="user-transactions">Rewards Vouchers</h2>
        <div class="available-ranks-vauchers">
            <div class="header-ranks-vouchers">
                <div class="voucher-name">Name</div>
                <div class="voucher-point">Points</div>
                <div class="voucher-point">Must Have</div>
                <div class="voucher-get">Action</div>
            </div>
            <?php foreach ($rewards['code'] as $item => $reward) { ?>
                <div class="voucher-select-item">
                    <div class="voucher-name"><?php echo esc_html($rewards['name'][$item]); ?></div>
                    <div class="voucher-point"><?php echo esc_html($rewards['points'][$item]); ?></div>
                    <div class="voucher-point"><?php echo esc_html($rewards['need'][$item]); ?> pts.</div>
                    <div class="voucher-get"><a href="<?php if($points >= $rewards['need'][$item] && $used_vouchers[$item] != 1) {echo "?get-rewards=".esc_html($reward);} ?>" <?php if($used_vouchers[$item] == 1) {echo 'class="used-voucher"';} elseif($points < $rewards['need'][$item]) {echo 'class="disabled"';} ?>>Take Points</a></div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
    <div style="display: none" class="wpm-rewards-notice">Keep your <b>eyes peeled</b> for other ways to earn <b>points</b>!</div>
</div>
<style>
    .row.eyes-peeled {
        margin-top: 0px !important;
    }
    p {
        line-height: 1em !important;
    }
    .point-earned {
        display:none;
    }
    .woocommerce {
        background-color: #b2e1ec;
        border-radius:20px;
    }
    .wpm-rank-block , .wpm-rank-block-small {
        color: #fff;
    }
    .wpm-current-rank > div {
        background-color: #b2e1ec;
        border: 1px solid #047a96;
    }
</style>