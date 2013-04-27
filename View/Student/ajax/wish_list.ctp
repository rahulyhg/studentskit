<script type="text/javascript">
    $(document).ready(function(){
        //Activate tooltip
        initToolTips();
    });
</script>

<?php
////////////// Page 1 - start
if($page==1) {
?>
    <script type="text/javascript">
        $(document).ready(function(){
            var url = '/Student/wishList/{limit}/{page}';
            lmObj.loadMoreButton('#user-lessons-requests-load-more', 'click', '#user-lessons-requests', url, {}, 'get', <?php echo $limit; ?>);
            lmObj.setItemsCountSelector('#user-lessons-requests-load-more', '#user-lessons-requests div.lesson-box' );
        });
    </script>

    <p class="fontsize1 space8"><?php echo __('Here you can find lesson requests.'); ?></p>
    <?php
        echo $this->Layout->wishPopupButton(array('name'=>__('ADD'), 'class'=>'black-cent-butn2 add-blckbtn fontsize1 move-right',
                                                            'data-prepend-to'=>'user-lessons-requests', 'data-append-template'=>'user-panel'));
        echo $this->element('Home'.DS.'wish_popup');

        echo $this->element('Panel'.DS.'cancel_popup', array('buttonSelector'=>'.confirm-delete',
                                                        'title'=>__('Cancel your request'),
                                                        'description'=>__('Do you want to proceed?'),
                                                        'cancelUrl'=>array('controller'=>'Student', 'action'=>'disableRequest', '{id}')));
    ?>

    <div class="add-sub pull-left space3" id="user-lessons-requests">

<?php
}

    foreach($response['response']['wishList'] AS $wishData) {
        echo $this->element('Panel'.DS.'user_wish_list_div', array('wishData'=>$wishData));
    }


////////////// Page 1 - start
if($page==1) {
    ?>
    </div>

    <?php
    if(count($response['response']['wishList'])>=$limit) {
        echo '<a href="#" class="more radius3 gradient2 space8" id="user-lessons-requests-load-more"><strong>', __('Load More') ,'</strong><i class="iconSmall-more-arrow"></i></a>';
    }
}
////////////// Page 1 - end
?>