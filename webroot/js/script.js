/////////////////////////////////////////////////////////////// Helpers
/**
 * Auto JS files loader.
 * Help to load JS files if objects are missing - in other words, helps to load JS files only once.
 */
function autoJSLoader() {

}
autoJSLoader.prototype.loadScript = function (src, callback) {
    var head=document.getElementsByTagName('head')[0];
    var script= document.createElement('script');
    script.type= 'text/javascript';
    script.onreadystatechange = function () {
        if (this.readyState == 'complete' || this.readyState == 'loaded') {
            callback();
        }
    }
    script.onload = callback;
    script.src = src;
    head.appendChild(script);
}

autoJSLoader.prototype.isLoaded = function (objectType) {
    var global = window;

    var o = objectType.split('.');
    for (var i in o) {
        if(!global[o[i]]) {
            return false;
        }
        global = global[o[i]];
    }

    return true;
}

autoJSLoader.prototype.tryLoadChain = function (objectType, chain, runOnLoad) {
    if (!this.isLoaded(objectType)) {
        if (chain.length) {
            this.loadScript(
                chain[0],
                function() {
                    if(runOnLoad) {
                        runOnLoad(chain[0]);
                    }
                    if(chain.length>1) {
                        this.tryLoadChain.apply(this.tryLoadChain, Array.prototype.slice.call(chain, 1));
                    }
                }
            );
        }
    }
}

/**
 * Used to post a form and replace its container with the (HTML) result
 * @constructor
 */
function PostForm() {
    this.forms = {};
    this.callbacks = {before:{}, after:{}, beforeAjax:{}};
}

PostForm.prototype.setAppendCallback = function( formSelector, on, func ) {
    this.callbacks[on][formSelector] = func;
}
PostForm.prototype.getAppendCallback = function( formSelector, on ) {
    if(this.callbacks[on][formSelector]) {
        return this.callbacks[on][formSelector];
    }

    return false;
}

/**

 *
 * Please note, on ajax pages (I.e student's profile). the loading JS command must exists in it:
 *  pfObj.loadForm('#user-profile-form', '#main-area', 'post');
 *  otherwise - the JS will word only for the first time.
 *
 * @param formSelector
 * @param appendResultsSelector
 * @param type
 * @param params
 */
PostForm.prototype.loadForm = function( formSelector, appendResultsSelector, type, params ) {
    var postForm = this;

    $('body').undelegate(formSelector, 'submit');
    $('body').delegate(formSelector, 'submit', function(event) {

        //Get form action (url)
        var url = $(this).attr('action');

        if(!params) {
            var params = $(this).data();
        }

        //Remove hidden inputs
        $(this).find('.load-element-dynamic').remove();

        //Append data-* as hidden:input
        formData = $(this).data();
        $.each(formData, function(key, val){
            $('<input>').attr('type','hidden').attr('name', key).attr('class', 'load-element-dynamic').attr('value', val).appendTo(formSelector);
        });

        //Before we call Ajax
        beforeAjaxCallback = postForm.getAppendCallback(formSelector, 'beforeAjax');
        if(beforeAjaxCallback) {
            data = beforeAjaxCallback( data );
            if(!data) {
                return false;
            }
        }

        $.ajax({
            url: jQuery.nano(url, params),
            type: type,
            data: $(this).serialize(),
            dataType: 'html'

        }).done(function ( data ) {
                beforeCallback = postForm.getAppendCallback(formSelector, 'before');
                if(beforeCallback) {
                    data = beforeCallback( data );
                    if(!data) {
                        return false;
                    }
                }

                //replace the returned HTML with the current one
                $(appendResultsSelector).html(data);

                afterCallback = postForm.getAppendCallback(formSelector, 'after');
                if(afterCallback) {
                    afterCallback( data );
                }
            });

        //Stop from from submitting itself
        return false;
    });
}


/**
 * Used to post the form data, if any erros - append them
 */

function PostAPI() {
    this.forms = {};
    this.callbacks = {before:{}, after:{}, beforeAjax:{}};
}

PostAPI.prototype.setAppendCallback = function( formSelector, on, func ) {
    this.callbacks[on][formSelector] = func;
}
PostAPI.prototype.getAppendCallback = function( formSelector, on ) {
    if(this.callbacks[on][formSelector]) {
        return this.callbacks[on][formSelector];
    }

    return false;
}


PostAPI.prototype.loadElement = function( formSelector, onEvent, appendErrorsSelector, type ) {
    var postFormAPI = this;

    //Remove all event binding
    $('body').undelegate(formSelector, onEvent);
    $('body').delegate(formSelector, onEvent, function(event) {

        //Link data-* attributes will be used as data
        var urlParams = $(this).data();
        var dataParams = {};

        //Check if this is a form
        if($(this).attr('action')) {

            //Remove hidden inputs
            $(this).find('.load-element-dynamic').remove();

            //Convert the data-* into hidden inputs
            if(urlParams) {
                var form = $(this);
                $.each(urlParams, function(key, val){
                    $('<input>').attr('type','hidden').attr('name', key).attr('class', 'load-element-dynamic').attr('value', val).appendTo(form);
                });
            }


            //Form inputs will be used as data
            dataParams = $(this).serialize();

            //Get form action - this will be used as url
            var url = $(this).attr('action');

        } else {
            //data-target
            var url = urlParams['target'];
            if($(this).val()) {
                urlParams['value'] = $(this).val();
            }

            dataParams = urlParams;
        }

        beforeAjaxCallback = postFormAPI.getAppendCallback(formSelector, 'beforeAjax');
        if(beforeAjaxCallback) {
            dataParams = beforeAjaxCallback( dataParams );
            if(!dataParams) {
                return false;
            }
        }

        $.ajax({
            url: jQuery.nano(url, urlParams),
            type: type,
            data: dataParams,
            dataType: 'json'

        }).done(function ( data ) {
                beforeCallback = postFormAPI.getAppendCallback(formSelector, 'before');
                if(beforeCallback) {
                    data = beforeCallback( data );
                    if(!data) {
                        return false;
                    }
                }

                if(appendErrorsSelector && data['response']['title'][0]=='Error') {
                    /*//Show error
                    var msg = '';

                    var validationErrors = {};
                    if(data['response']['validation_errors']!=undefined) {
                        validationErrors = data['response']['validation_errors'];
                    } else if(data['response']['results'] && data['response']['results']['validation_errors']!=undefined) {
                        validationErrors = data['response']['results']['validation_errors'];
                    }

                    $.each(validationErrors, function(key, val) {
                        msg += val[0] + '<br />';
                    });

                    showError(appendErrorsSelector, data['response']['description'][0], msg);*/
                    var parseData = parseResponse(data);
                    showError(appendErrorsSelector, parseData['title'], parseData['des']);

                    return false;
                }

                afterCallback = postFormAPI.getAppendCallback(formSelector, 'after');
                if(afterCallback) {
                    afterCallback( data );
                }
            });

        //Stop from from submitting itself
        return false;
    });
}

function parseResponse(data) {


    if(data['response']['title'][0]=='Error') {
        var msg = '';

        var validationErrors = {};
        if(data['response']['validation_errors']!=undefined) {
            validationErrors = data['response']['validation_errors'];
        } else if(data['response']['results'] && data['response']['results']['validation_errors']!=undefined) {
            validationErrors = data['response']['results']['validation_errors'];
        }

        $.each(validationErrors, function(key, val) {
            msg += val[0] + '<br />';
        });

        return {
            type: data['response']['title'][0],
            title: data['response']['description'][0],
            des: msg
        };
    } else {
        return {
            type: data['response']['title'][0],
            title: data['response']['description'][0],
            des: ''
        };
    }


}

/////////////////////////////////////////////////////////////// panel + site

function initSubjectForm(oneOnOnePriceInputSelector, lessonTypeInputSelector,
                         maxStudentsInputSelector, maxStudentsDivSelector,
                         fullGroupStudentPriceDivSelector, fullGroupStudentPriceInputSelector,
                         durationDivSelector) {


    oneOnOnePriceInputSelector          = 'div:visible ' + oneOnOnePriceInputSelector; //#sub-area
    lessonTypeInputSelector             = 'div:visible ' + lessonTypeInputSelector;
    maxStudentsInputSelector            = 'div:visible ' + maxStudentsInputSelector;
    maxStudentsDivSelector              = 'div:visible ' + maxStudentsDivSelector;
    fullGroupStudentPriceDivSelector    = 'div:visible ' + fullGroupStudentPriceDivSelector;
    fullGroupStudentPriceInputSelector  = 'div:visible ' + fullGroupStudentPriceInputSelector;
    durationDivSelector                 = 'div:visible ' + durationDivSelector;

    $(oneOnOnePriceInputSelector).unbind();
    $(fullGroupStudentPriceInputSelector).unbind();
    $(maxStudentsInputSelector).unbind();
    $(lessonTypeInputSelector).unbind();


    //Chrome fix, hidden inputs that are required
    $(oneOnOnePriceInputSelector).closest('form').attr('novalidate', 'novalidate');

    //Make Full-group-student-price invisible until the user set max-students>1
    $(fullGroupStudentPriceDivSelector).hide();

    //1. User change the value of 1on1 price
    $(oneOnOnePriceInputSelector).change(function(){
        if($(this).val()>0 && $(maxStudentsInputSelector).val()>1) {
            $(fullGroupStudentPriceDivSelector).show();
        } else {
            $(fullGroupStudentPriceDivSelector).hide();
        }

        //If there is no group price or group price is higher then 1 on 1 price
        if($(fullGroupStudentPriceInputSelector).val()=='' || $(fullGroupStudentPriceInputSelector).val()>$(this).val()) {

            //Set 1on1 price on group price
            $(fullGroupStudentPriceInputSelector).val($(this).val());
        }

    });

    //Make sure that the group price is equal or lower then 1on1 price
    $(fullGroupStudentPriceInputSelector).change(function(){
        if($(this).val()>$(oneOnOnePriceInputSelector).val()) {
            $(this).val($(oneOnOnePriceInputSelector).val());
        }
    });

    //Show/Hide student full group price - by max-students
    $(maxStudentsInputSelector).change(function(){
        //Show group student price
        if($(this).val()>1 && $(oneOnOnePriceInputSelector).val()>0) { //show it only if 1on1price>0
            $(fullGroupStudentPriceDivSelector).show();

            //Hide it
        } else {
            $(fullGroupStudentPriceDivSelector).hide();
        }
    });

    //If lesson type is video, hide max students and full-group-price
    $(lessonTypeInputSelector).change(function(){
        if($(this).val()=='live') {
            $(maxStudentsDivSelector).show();
            $(durationDivSelector).show();

            //file max-students change
            $(maxStudentsInputSelector).trigger('change');

        } else {
            $(maxStudentsDivSelector).hide();
            $(durationDivSelector).hide();
            $(fullGroupStudentPriceDivSelector).hide();
        }
    });

    $(oneOnOnePriceInputSelector).change();
    $(fullGroupStudentPriceInputSelector).change();
    $(maxStudentsInputSelector).change();
    $(lessonTypeInputSelector).change();
}

function resetData(elementSelector) {
    if($(elementSelector).data()) {
        //console.log($(elementSelector).data());
        $.each($(elementSelector).data(), function(key, val){
            $(elementSelector).removeData(key);
        });
    }
}
/////////////////////////////////////////////////////////////// panel

/* Back office tabs */

//Old Tabs
/*$(document).ready(function(){
    $(".load").click(function(){
        $(".loadpage").load("/ajax/"+$(this).attr('rel'));
        $(".booking-nav li").removeClass("active");
        $(this).parent("li").addClass("active");
    });
});*/
//New Tabs
function initTabs(loadActive) {
    return false;
    if(loadActive==undefined) {
        loadActive = true;
    }

    $(".load3").click(function(){
        //If the parent have class of .disable - ignore it
        if($(this).parent().hasClass('disable')) {
            return false;
        }
        //If element have class of .disable - ignore it
        if($(this).hasClass('disable')) {
            return false;
        }

        $(".loadpage").load($(this).attr('rel'));
        $(".booking-nav li").removeClass("active");
        $(this).parent("li").addClass("active");
    });

    if(loadActive) {
        //Load the first tab
        $(".tab-menu li").each(function(){
            if($(this).hasClass("active")){
                //	alert();
                $(".loadpage").load($(this).children("a").attr("rel"));
            }
        });
    }
}


//Organizer - Old menu link
$(document).ready(function(){
    $(".load1").click(function(){
        $("#main-area").load("/ajax/"+$(this).attr('rel'));
        $(".right-menu li").removeClass("bg-active");
        $(this).parent("li").addClass("bg-active");
    });

});

/* User/Teacher rating */
$(document).ready(function(){

    $('#main-area').delegate('.star-box.dynamic', 'mouseover', function(event) {
        $(this).addClass('star-active');
        $(this).prevAll().addClass('star-active');
    });
    $('#main-area').delegate('.star-box.dynamic', 'mouseout', function(event) {
        $(this).removeClass('star-active');
        $(this).prevAll().removeClass('star-active');
    });
    $('#main-area').delegate('.star-box.dynamic', 'click', function(event) {
        if ($(this).hasClass('star-active1'))
        {
            //if(($(this).attr("id"))==1){
            if($(this).hasClass('first')){
                if(!$(this).nextAll().hasClass('star-active1'))
                {

                    $(this).removeClass('star-active1');
                }
            }
            $(this).nextAll().removeClass('star-active1');
        }
        else
        {
            $(this).addClass('star-active1');
            $(this).prevAll().addClass('star-active1');
        }
    });
});



// manage subject
function enableNextTabAndUpdateCurrentCreationStage(updateUrl, subjectId, newCreationStage) {
    $.ajax({
        url: jQuery.nano(   updateUrl,
            {subject_id: subjectId, creation_stage: newCreationStage}),
        type: 'get',
        //data: $(this).serialize(),
        dataType: 'json'

    }).done(function ( data ) {
            if(data['response']['title'][0]=='Error') {
                //Show error
                showError('#subjectContainer', data['response']['description'][0], '');
            } else {
                //Just clear errors
                showError('#subjectContainer');

                //Enable next tab

                //Find the next tab
                var nextTabId;
                switch(newCreationStage) {
                    case 1: //Subject done, open meeting tab
                        nextTabId = '#meetingTab';
                        break;
                    case 2: //Meeting done, open files
                        nextTabId = '#filesTab';
                        break;
                    case 3: //Files done, open test
                        nextTabId = '#testsTab';
                        break;
                    case 4: //Tests done, open publish
                        nextTabId = '#publishTab';
                        break;
                    case 5: //Publish done, close this tab and show the subject tab
                        $('#publishTab').hide('slow');
                        nextTabId = '#subjectTab';
                        break;

                }
                if(nextTabId) {
                    $(nextTabId).removeClass('disable');
                    initTabs();
                    $( nextTabId + ' a').click();
                }
            }
        });

}

//When click on nextButton - execute enableNextTabAndUpdateCurrentCreationStage(data-subject-id, data-creation-stage)
function initNextButton(updateUrl) {
    $('.nextButton').click(function() {
        enableNextTabAndUpdateCurrentCreationStage(updateUrl, $(this).data('subject-id'), $(this).data('creation-stage'));
        return false;
    });
}


//////////////////
var lastLoad3;

$(function() {
    var BBQWidgets = {
        '.load2': {
                    containerSelector: '#main-area',
                    markButtonBasedOnHash: function(clickedElement) {
                        var url = $.bbq.getState( BBQWidgets['.load2'].containerSelector ) || '';
                        if(url!='') {
                            clickedElement = $( 'a[rel="' + url + '"].load2:visible');
                        }

                        //Cover urls like /Teacher/lessonsArchive/2/1/0/15
                        if(!clickedElement.length) {
                            $( 'a.load2:visible').each(function(index, value) {
                                if(url.indexOf($(value).attr('rel'))==0) {
                                    clickedElement = $(this);
                                }
                            });
                        }

                        if(!clickedElement.length) {
                            return false;
                        }

                        //Hide
                        $('#sub-area').children( ':visible' ).hide();

                        //Mark link
                        $(".right-menu li").removeClass("bg-active");
                        $(clickedElement).parent("li").addClass("bg-active");

                    },
                    click: function(clickedElement, state){
                        BBQWidgets['.load2'].markButtonBasedOnHash(clickedElement);

                        if( $.bbq.getState( BBQWidgets['.load3'].containerSelector) ) {
                            $.bbq.removeState('#sub-area');
                            var containerData = $('#sub-area').data( 'bbq' );
                            containerData.url = '';
                            lastLoad3 = undefined;
                        }
                        return state;
                    },
                    emptyUrl: function() {

                        //Load the marked button
                        var firstMenuLink = $('ul.right-menu:visible li.bg-active a');
                        if(!firstMenuLink.length) {
                            //Load the first button
                            firstMenuLink = $('ul.right-menu:visible li:first a');;
                        }
                        if(firstMenuLink.length) {
                            $(firstMenuLink).click();
                        }
                    },
                    afterLoad: function() {
                        var url = $.bbq.getState( BBQWidgets['.load3'].containerSelector ) || '';
                        if(url=='') {
                            BBQWidgets['.load3'].emptyUrl();
                        }

                        lmObj.reset();
                    }},
        '.load3': {
                    containerSelector: '#sub-area',

                    markButtonBasedOnHash: function(clickedElement) {
                        var url = $.bbq.getState( BBQWidgets['.load3'].containerSelector ) || '';
                        if(url!='') {
                            clickedElement = $( 'a[rel="' + url + '"].load3:visible' );
                        }

                        //Cover urls like /Teacher/lessonsArchive/2/1/0/15
                        if(!clickedElement.length) {
                            $( 'a.load3:visible').each(function(index, value) {
                                if(url.indexOf($(value).attr('rel'))==0) {
                                    clickedElement = $(this);
                                }
                            });
                        }

                        if(!clickedElement.length) {
                            return false;
                        }

                        //If the parent have class of .disable - ignore it
                        if($(clickedElement).parent().hasClass('disable')) {
                            return false;
                        }
                        //If element have class of .disable - ignore it
                        if($(clickedElement).hasClass('disable')) {
                            return false;
                        }

                        $(".booking-nav li").removeClass("active");
                        $(clickedElement).parent("li").addClass("active");
                    },
                    click: function(clickedElement, state){
                        BBQWidgets['.load3'].markButtonBasedOnHash(clickedElement);



                        return state;
                    },
                    emptyUrl: function() {

                        //Load the marked tab
                        var firstTab = $(".tab-menu:visible li.active a");
                        if(!firstTab.length) {
                            //Load the first tab
                            firstTab = $(".tab-menu:visible li:first a");
                        }
                        if(firstTab.length) {
                            if(lastLoad3!=$(firstTab).attr('rel')) {
                                $(firstTab).click();
                            } else if(lastLoad3) {
                                history.back(1);
                            }
                        }
                        lastLoad3 = $(firstTab).attr('rel');
                    },
                    afterLoad: function() {
                        lmObj.reset();
                        BBQWidgets['.load3'].markButtonBasedOnHash(); //Fix on refresh
                    }
    }};


    function initCache() {
        // For each widget, keep a data object containing a mapping of
        // url-to-container for caching purposes.
        $.each(BBQWidgets, function(linkSelector, data){
            //There is no data set
            if(!$(data.containerSelector).data( 'bbq')) {
                $(data.containerSelector).data( 'bbq', {
                    cache: {
                        // If url is '' (no fragment), display this div's content.
                        '': $(this)
                    }
                });
            }
        });
    }


    // For all links inside a widget, push the appropriate state onto the
    // history when clicked.
    $.each(BBQWidgets, function(linkSelector, data){
        $( linkSelector ).live( 'click', function(e){

            var state = {},

            // Get the url from the link's href attribute
                url = $(this).attr( 'rel' );
            //.replace( /^#/, '' ); // stripping any leading #.
            state[ data.containerSelector ] = url;

            if(BBQWidgets[linkSelector].click) {
                state = BBQWidgets[linkSelector].click(this, state);
                if(!state) {
                    e.stopPropagation();
                    return false;
                }
            }



            // Set the state!
            $.bbq.pushState( state );

            // And finally, prevent the default link click behavior by returning false.
            return false;
        });
    });


    // Bind an event to window.onhashchange that, when the history state changes,
    // iterates over all .bbq widgets, getting their appropriate url from the
    // current state. If that .bbq widget's url has changed, display either our
    // cached content or fetch new content to be displayed.
    $(window).bind( 'hashchange', function(e) {
        initCache();

        $.each(BBQWidgets, function(linkSelector, data){
            // Iterate over all  widgets.
            $(data.containerSelector).each(function(){
                var that = $(this),

                // Get the stored data for this .bbq widget.
                    containerData = that.data( 'bbq' ),

                // Get the url for this .bbq widget from the hash, based on the
                // appropriate id property. In jQuery 1.4, you should use e.getState()
                // instead of $.bbq.getState().
                    url = $.bbq.getState( data.containerSelector ) || '';

                //On empty URL
                if(url=='' && BBQWidgets[linkSelector].emptyUrl) {
                    BBQWidgets[linkSelector].emptyUrl();
                    return false;
                }

                // If the url hasn't changed, do nothing and skip to the next .bbq widget.
                if ( containerData.url === url ) { return; }

                // Store the url for the next time around.
                containerData.url = url;

                //Set last loaded
                if(linkSelector=='.load3') {
                    lastLoad3 = url;
                }

                if(BBQWidgets[linkSelector].markButtonBasedOnHash) {
                    BBQWidgets[linkSelector].markButtonBasedOnHash();
                }

                // Remove .bbq-current class from any previously "current" link(s).
                $( 'a.bbq-current' + linkSelector ).removeClass( 'bbq-current' );

                // Hide any visible ajax content.
                that.children( ':visible' ).hide();

                // Add .bbq-current class to "current" nav link(s), only if url isn't empty.
                url && $( 'a[rel="' + url + '"]' + linkSelector ).addClass( 'bbq-current' );


                $.ajax({
                    url: url,
                    dataType: 'html'

                }).done(function ( data ) {
                        containerData.cache[ url ] = data;
                        that.html(data);

                        if(BBQWidgets[linkSelector].afterLoad) {
                            BBQWidgets[linkSelector].afterLoad();
                        }

                        preventNullLinks();
                    });

                return false;

                if ( containerData.cache[ url ] ) {
                    // Since the widget is already in the cache, it doesn't need to be
                    // created, so instead of creating it again, let's just show it!
                    //containerData.cache[ url ].show();

                    that.html(containerData.cache[ url ]);

                    if(BBQWidgets[linkSelector].afterLoad) {
                        BBQWidgets[linkSelector].afterLoad();
                    }

                } else {
                    // Show "loading" content while AJAX content loads.
                    that.find( '.bbq-loading' ).show();


                    /*// Create container for this url's content and store a reference to it in
                    // the cache.
                    containerData.cache[ url ] = $( '<div class="bbq-item"/>' )

                        // Append the content container to the parent container.
                        .appendTo( that )

                        // Load external content via AJAX. Note that in order to keep this
                        // example streamlined, only the content in .infobox is shown. You'll
                        // want to change this based on your needs.
                        .load( url, function(){
                            // Content loaded, hide "loading" content.
                            that.find( '.bbq-loading' ).hide();

                            if(BBQWidgets[linkSelector].afterLoad) {
                                BBQWidgets[linkSelector].afterLoad();
                            }
                        });*/

                    $.ajax({
                        url: url,
                        dataType: 'html'

                    }).done(function ( data ) {
                            containerData.cache[ url ] = data;
                            that.html(data);

                            if(BBQWidgets[linkSelector].afterLoad) {
                                BBQWidgets[linkSelector].afterLoad();
                            }
                    });
                }
            });
        })
    });

    // Since the event is only triggered when the hash changes, we need to trigger
    // the event now, to handle the hash the page may have loaded with.
    $(window).trigger( 'hashchange' );


});
//////////////////









//Organizer - New menu links
function initMenuLinks() {
    return false;
    $(".load2").unbind();
    $(".load2").click(function(e){
        $(".loadpage1").load($(this).attr('rel'));
        $(".right-menu li").removeClass("bg-active");
        $(this).parent("li").addClass("bg-active");
    });
}
/*$(document).ready(function(){
    initMenuLinks();
});*/


/*

//Boxes action buttons
function ActionButtons() {
    this.buttonSelectors = {};
    this.callbacks = {before:{}, after:{}};
}
ActionButtons.prototype.setCallback = function( buttonSelector, on, func ) {
    this.callbacks[on][buttonSelector] = func;
}
ActionButtons.prototype.getdCallback = function( buttonSelector, on ) {
    if(this.callbacks[on][buttonSelector]) {
        return this.callbacks[on][buttonSelector];
    }

    return false;
}
ActionButtons.prototype.loadButton = function(buttonSelector, url, type, params) {
    var actionButton = this;

    $(buttonSelector).click(function(event) {
        event.preventDefault();

        beforeCallback = postFormAPI.actionButton(buttonSelector, 'before');
        if(beforeCallback) {
            if(!beforeCallback( data )) {
                return false;
            }
        }

        $.ajax({
            url: jQuery.nano(url, params),
            type: type,
            data: params,
            dataType: 'json'

        }).done(function ( data ) {

                if(data['response']['title'][0]=='Error') {
                    //Show error
                    var msg = '';
                    $.each(data['response']['validation_errors'], function(key, val) {
                        msg += val[0] + '<br />';
                    });
                    showError(appendErrorsSelector, data['response']['description'][0], msg);

                }

                afterCallback = postFormAPI.getAppendCallback(buttonSelector, 'after');
                if(afterCallback) {
                    afterCallback( data );
                }
            });

        //Stop from from submitting itself
        return false;
    });
}
*/


var pfObj = new PostForm();
var pAPIObj = new PostAPI();
var autoJSLoaderObj = new autoJSLoader();




/////////////////////////////////////////////////////////////// site



/*  live join subject page */
function LoadMore() {
    this.paginator = {};
    this.callbacks = {before:{}, after:{}, clearBeforeAppend:{}, itemCount:{}};
}
LoadMore.prototype.getNextPage = function(buttonSelector) {
    if(!this.paginator[buttonSelector]) {
        this.paginator[buttonSelector] = 2;
    } else {
        this.paginator[buttonSelector]++;
    }

    return this.paginator[buttonSelector];
}
LoadMore.prototype.getPrevPage = function(buttonSelector) {
    if(!this.paginator[buttonSelector]) {
        this.paginator[buttonSelector] = 1;
    } else {
        this.paginator[buttonSelector]--;
    }

    return this.paginator[buttonSelector];
}
LoadMore.prototype.reset = function(buttonSelector) {
    if(buttonSelector) {
        this.paginator[buttonSelector] = 1;
    } else {
        this.paginator = {};
    }
}

LoadMore.prototype.clearBeforeAppend = function(buttonSelector, status ) {
    this.callbacks.clearBeforeAppend[buttonSelector] = status;
}


LoadMore.prototype.loadMoreButton = function(buttonSelector, eventName, appendToSelector, url, params, type, limit, excludeGetParams) {

    var loadMoreObj = this;

    $('body').undelegate(buttonSelector, eventName);
    $('body').delegate(buttonSelector, eventName, function(e) {
        e.preventDefault();
        params['rnd'] = Math.random(); //to avoid cache
        params['limit'] = limit;
        params['page'] = loadMoreObj.getNextPage(buttonSelector);

        if(excludeGetParams) {
            for(i in excludeGetParams) {
                delete params[excludeGetParams[i]];
            }
        }

        $.ajax({
            url: jQuery.nano(url, params),
            type: type,
            data: params,
            dataType: 'html'

        }).done(function ( data ) {
            beforeCallback = loadMoreObj.getAppendCallback(buttonSelector, 'before');
            if(beforeCallback) {
                data = beforeCallback( data );
                if(!data) {
                    return false;
                }
            }

            var itemCountMethod = loadMoreObj.getAppendCallback(buttonSelector, 'itemCount');
            if(!itemCountMethod) {
                //Default action
                if(!data) {
                    $(buttonSelector).css('visibility', 'hidden');
                    return false;
                }
            } else {
                //No items loaded, call user func
                var currentCount = $(itemCountMethod.itemCountSelector).length;
                if(!data) {
                    return itemCountMethod.func(currentCount, 0, limit);
                }
            }


            //Check if need to clear current results
            if(loadMoreObj.getAppendCallback(buttonSelector, 'clearBeforeAppend')) {
                $(appendToSelector).html('');
            }

            //Append results
            $(data).appendTo(appendToSelector);

            if(itemCountMethod) {
                var newCount = $(itemCountMethod.itemCountSelector).length;
                itemCountMethod.func(currentCount, newCount, limit);
            }

            afterCallback = loadMoreObj.getAppendCallback(buttonSelector, 'after');
            if(afterCallback) {
                afterCallback( data );
            }
        }).error(function(data){
            //Set prev page
            loadMoreObj.getPrevPage(buttonSelector);
        });

        //return false;
     });

};
LoadMore.prototype.setItemsCountSelector = function( buttonSelector, itemCountSelector, func ) {
    if(func==undefined) {
        func = function(currentCount, newCount, limit) {
            //Not enough new items
            if(currentCount+limit>newCount) {
                $(buttonSelector).css('visibility', 'hidden');
            }
        }
    }
    this.setAppendCallback(buttonSelector, 'itemCount', {
        itemCountSelector: itemCountSelector,
        func: func
    });

}
LoadMore.prototype.setAppendCallback = function( buttonSelector, on, func ) {
    this.callbacks[on][buttonSelector] = func;
}
LoadMore.prototype.getAppendCallback = function( buttonSelector, on ) {
     if(this.callbacks[on][buttonSelector]) {
         return this.callbacks[on][buttonSelector];
     }

    return false;
}

var lmObj = new LoadMore();

function showSuccess(inSelector, title, msg, autoFadeMilli) {
    showMessage(inSelector, title, msg, autoFadeMilli, 'alert-success');
}
function showError(inSelector, title, msg, autoFadeMilli) {
    showMessage(inSelector, title, msg, autoFadeMilli, 'alert');
}
function showMessage(inSelector, title, msg, autoFadeMilli, msgClass) {
    if(autoFadeMilli==undefined) {
        autoFadeMilli = 3000;
    }

    //There is no need to add .alert, because its already in the templete below
    var appendClass = msgClass;
    if(msgClass=='alert') {
        appendClass = '';
    }

    var obj = $(inSelector);

    $(obj).find('.' + msgClass).remove(); //Remove old alert msg

    if(title==undefined || msg==undefined) {
        return false;
    }

    //Auto fade after 3 sec
    if(autoFadeMilli) {
        $(obj).prepend('<div class="alert ' + appendClass + ' fade in"> <strong>'+ title +' </strong>'+ msg +'</div>'); //Append new alert msg

        var messageContainer = $(obj).find('.' + msgClass);
        setTimeout(function(){$(messageContainer).fadeOut()}, autoFadeMilli);
    } else {
        $(obj).prepend('<div class="alert ' + appendClass + ' fade in"> <button type="button" class="close" data-dismiss="alert">×</button> <strong>'+ title +' </strong>'+ msg +'</div>'); //Append new alert msg with close button

    }
}

//Copy ids from A to model
function initCopyIdLinks() {
    $(".copyDataId").click(function () {
        $($(this).data('hidden-input')).val($(this).data('id'))
    });
}

function initToolTips() {
    $("[rel=tooltip]").tooltip({html: true});
}

function preventNullLinks() {
    //Disable links anchor default <a href="#">..
    //Without it - the user screen will jump
    $('a[href="#"]').click(function(e) {
        e.preventDefault();
    });
}

$(document).ready(function(){
    //Activate tooltip
    initToolTips();

    preventNullLinks();
});
///////////////////////////////////////////// Login/registration management
function globalAjaxHandler() {
    this.currentRequests = 0;
    this.status = 0;
    this.showHideProgressBar = function() {
        if(this.currentRequests<=0) {
            if(this.status!=0) {
                $('#ajaxInProgress').hide();
                this.status = 0;
            }
        } else if(this.currentRequests>=1) {
            if(this.status!=1) {
                $('#ajaxInProgress').show();
                this.status = 1;
            }
        }
    }
}
globalAjaxHandler.prototype.beforeSend = function(event, request, options, error) {
    //Show progress bar
    this.currentRequests++;
    this.showHideProgressBar();
}
globalAjaxHandler.prototype.complete = function(event, request, options, error) {
    //Hide progress bar
    this.currentRequests--;
    this.showHideProgressBar();
}
globalAjaxHandler.prototype.error = function(event, request, options, error) {
    switch (event.status) {
        case 403: //Forbidden - caused by users that not logged in
            $('#login-popup').modal('show');
            break;
    }
}
var gah = new globalAjaxHandler();


$(document).ready(function(){

    //Make sure the user logged in
    $.ajaxSetup(gah);

    //Make sure .requireLogin elements will popup the login-form first and cancel other event-listeners
    $('.requireLogin').click(function() {
        if(!jsSettings['user_id']) {
            //TODO: make sure this is the first event
            $('#login-popup').modal('show');
            return false;
        }
    });

    //Login form JS
    $('#login-form').submit(function() {
        $.post(
            '/login.json',
            $(this).serialize(),
            function(data){
                if(data['response']['title'][0]=='Error') {
                    //Show error
                    showError('#login-form .modal-body', data['response']['title'][0], data['response']['description'][0]);

                } else {
                    //Login Success
                    jsSettings['user_id'] = data['response']['user_id'];
                    updateTopBar();
                    $('#login-popup').modal('hide');
                }
            }
        );

        return false;
    });

    //Registration form JS
    $('#register-form').submit(function() {
        $.post(
            '/register.json',
            $(this).serialize(),
            function(data){
                if(data['response']['title'][0]=='Error') {
                    //Show error
                    var msg = '';
                    $.each(data['response']['validation_errors'], function(key, val) {
                        msg += val[0] + '<br />';
                    });
                    showError('#register-form .modal-body', data['response']['description'][0], msg);

                } else {
                    //Login Success
                    jsSettings['user_id'] = data['response']['user_id'];
                    updateTopBar();
                    $('#register-popup').modal('hide');
                }
            }
        );

        return false;
    });

    function updateTopBar() {
        location.reload(); //Reload page
    }
});

///////////////////////////////////////////// Order load more join lessons
$(document).ready(function(){
    /* Order join load more */

    //Scroll
    $('#upcoming .modal-body').slimScroll({
        height: '200px',
        start: 'top',
        disableFadeOut: true
    });

    var url = '/Order/getUpcomingOpenLessonForSubject/{subject_id}/{limit}/{page}';

    lmObj.loadMoreButton('.upcoming-lessons-for-subject', 'click', '#upcoming .modal-body', url, jsSettings, 'get', 3);
    lmObj.setItemsCountSelector('.upcoming-lessons-for-subject', '#upcoming .modal-body .main-student' );
});

///////////////////////////////////////////// Home/Requests auto complete
$(document).ready(function(){
    $( "#term" ).autocomplete({ source: jsSettings['search_suggestions_url'] });
});

///////////////////////////////////////////// Home/Requests search load more
$(document).ready(function(){
    /* Home/Requests search load more */

    var url = '{search_load_more_url}&limit={limit}&page={page}';

    lmObj.loadMoreButton('a.search-load-more', 'click', 'ul.lesson-container', url, jsSettings, 'get', 8);
    lmObj.setItemsCountSelector('a.search-load-more', 'ul.lesson-container li' );
});

///////////////////////////////////////////// Teacher/TeacherSubject page
$(document).ready(function(){
    /* My Subject */

    //Scroll
    if($('ul.subject-box li div').length) {
        $('.my-subject-box').slimScroll({
            height: '159px',
            start: 'top',
            width: '100%',
            disableFadeOut: true
        });
    }

    var url = '/Home/getTeacherSubjects/{teacher_user_id}/{limit}/{page}';
    if(jsSettings['subject_id']) {
        url = url + '/{subject_id}'
    }

    lmObj.loadMoreButton('a.mysubject-more', 'click', 'ul.subject-box', url, jsSettings, 'get', 3);
    lmObj.setItemsCountSelector('a.mysubject-more', 'ul.subject-box li' );
});

$(document).ready(function(){
    /* Upcoming lessons */

    //Scroll
    if($('ul.upcoming-more li').length) {
        $('div.up-coming').slimScroll({
            height: '90px',
            start: 'top',
            width: '100%',
            disableFadeOut: true
        });
    }

    var url = '/Home/getUpcomingOpenLesson/{teacher_user_id}/{limit}/{page}';
    if(jsSettings['subject_id']) {
        url = '/Home/getUpcomingOpenLessonForSubject/{subject_id}/{limit}/{page}';
    }

    lmObj.loadMoreButton('a.upcoming-more', 'click', 'ul.upcoming-more', url, jsSettings, 'get', 3);
    lmObj.setItemsCountSelector('a.upcoming-more', 'ul.upcoming-more li' );
});

$(document).ready(function(){
    /* Reviews by students for teacher/teacherSubject pages */

    $(document).ready(function(){
        if($('div.reviews-by-students div.student-review').length) {
            $('div.reviews-by-students').slimScroll({
                height: '135px',
                width: '100%',
                start: 'top'
            });
        }

        var url = '/Home/getTeacherRatingByStudents/{teacher_user_id}/{limit}/{page}';
        if(jsSettings['subject_id']) {
            var url = '/Home/getTeacherRatingByStudentsForSubject/{subject_id}/{limit}/{page}';
        }

        lmObj.loadMoreButton('a.reviews-by-students', 'click', 'div.reviews-by-students', url, jsSettings, 'get', 3);
        lmObj.setItemsCountSelector('a.reviews-by-students', 'div.reviews-by-students div.student-review' );
    });
});

///////////////////////////////////////////// User page
$(document).ready(function(){
    /* Reviews by teachers for user page */


    $(document).ready(function(){
        if($('div.reviews-by-teachers div.teacher-review').length) {
            $('div.reviews-by-teachers').slimScroll({
                height: '135px',
                width: '100%',
                start: 'top'
            });
        }

        lmObj.loadMoreButton('a.reviews-by-teachers', 'click', 'div.reviews-by-teachers', '/Home/getStudentRatingByTeachers/{student_user_id}/{limit}/{page}', jsSettings, 'get', 3);
        lmObj.setItemsCountSelector('a.reviews-by-teachers', 'div.reviews-by-teachers div.teacher-review' );
    });
});

$(document).ready(function(){
    /* user latest lessons */

    //Scroll
    if($('div.latest-lessons div.latest-lesson').length) {
        $('div.latest-lessons').slimScroll({
            height: '115px',
            alwaysVisible: false,
            start: 'top',
            wheelStep: 6
        });
    }

    lmObj.loadMoreButton('a.latest-lessons', 'click', 'div.latest-lessons', '/Home/getStudentArchiveLessons/{student_user_id}/{limit}/{page}', jsSettings, 'get', 5);
    lmObj.setItemsCountSelector('a.latest-lessons', 'div.latest-lessons div.latest-lesson' );
});

///////////////////////////////////////////// Home page
$(document).ready(function() {
    /* Home last board posts */

    //Scroll
    if($('ul.board-msg li').length) {
        $('.board-msg').slimScroll({
            height: '404px',
            alwaysVisible: false,
            start: 'top',
            width: '100%'
        });
    }
    lmObj.loadMoreButton('a.more-btn1', 'click', 'ul.board-msg', '/Home/latestBoardPosts/{limit}/{page}', jsSettings, 'get', 5);
    lmObj.setItemsCountSelector('a.more-btn1', 'ul.board-msg li' );
});

///////////////////////////////////////

/* Country, TZ, Lang script, Notification, Messages popups */
$(document).ready(function(){

    $(".show-tip").click(function(event){
        //Close all popups
        $(".alltip").hide(300);

        //Button ID is used to build the ID of the popup
        var id = $(this).attr('id');
        $('#' + id + '-tip').slideDown(300);

        event.stopPropagation();
    });

    $(".alltip").children().click(function(event){
        //If user click on records in the popup - make sure the popup stay open
        event.stopPropagation();
    });

    $("html").click(function(ev){
        $(".alltip").hide(300);
    });
});