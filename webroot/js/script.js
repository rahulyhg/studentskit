// JavaScript Document


/* Ajax tab script */

$(document).ready(function(){
  $(".load").live("click",function(){
$(".loadpage").load("/ajax/"+$(this).attr('rel'));
$(".booking-nav li").removeClass("active");
$(this).parent("li").addClass("active");
});

});
$(document).ready(function(){
  $(".load1").live("click",function(){
$(".loadpage1").load("/ajax/"+$(this).attr('rel'));
$(".right-menu li").removeClass("bg-active");
$(this).parent("li").addClass("bg-active");
});

});

/* tooltip script */
$(document).ready(function(){
	
	$(".show-tip").live("click",function(event){ 	
		var id=$(this).attr("id");		
		var visi=$("#"+id+"-tip").is(":visible");
		$(".alltip").hide();
		if(visi){
				$("#"+id+"-tip").hide(300);
		}else{
			$("#"+id+"-tip").slideDown(300);
			
		}
		event.stopPropagation();
		
	});
	$(".alltip").children().click(function(event){
		event.stopPropagation();
	});
	$("html").live("click",function(ev){
		//alert(ev.target.attr('class'));
		$(".alltip").hide();
	});
});
/* message notificatoin pressed */

/* more thread notificaiton pressed page */

$(document).ready(function(){

  $(".more-btn1").live("click",function(){
setTimeout(function(){$("#more").load("assets/more.html");},300);

  });

});

/* info tooltip */

$(document).ready(function(){
	
$(".show-info").live("mouseover",function(event){ 	
		var id=$(this).attr("id");		
		var visi=$("#"+id+"-tip").is(":visible");
		if(!visi){
			$("#"+id+"-tip").slideDown(0);
			
		}
		event.stopPropagation();
		
	});
   $(".info-pop").live("mouseover",function(){
		$(".info-pop").is("visible");							 
	});	
	
	$("html").live("mouseover",function(){
		$(".info-pop").slideUp(0);
	});
});


/* pager script */

$(document).ready(function(){
var currentpage=1;
var maxpage=4;
 $(".load").click(function(){
 currentpage=eval($(this).text());
if(currentpage>=maxpage)
{
$(".next").parent("li").addClass("disabled");
}
else
{
$(".next").parent("li").removeClass("disabled");
}
	if(currentpage>1)
	{
	$(".prev").parent("li").removeClass("disabled");
	}
	else
	{$(".prev").parent("li").addClass("disabled");
	}
    	$(".paging").load("/ajax/page"+currentpage+".html");
	$(".pager li").removeClass("active");
	$(this).parent("li").addClass("active");

  });
  
$(".next").click(function(){
	currentpage=currentpage+1;
if(currentpage>=maxpage)
{
$(".next").parent("li").addClass("disabled");
}
else
{
$(".next").parent("li").removeClass("disabled");
}
	if(currentpage>1)
	{
	$(".prev").parent("li").removeClass("disabled");
	}
		else
		{$(".prev").parent("li").addClass("disabled");
		}
		$(".load").parent("li").removeClass("active");
	$(".p"+currentpage).parent("li").addClass("active");
				$(".paging").load("/ajax/page"+currentpage+".html");
});
  
$(".prev").click(function(){

	if(currentpage>1)
	{
	currentpage=currentpage-1;
		$(".load").parent("li").removeClass("active");
	$(".p"+currentpage).parent("li").addClass("active");
	$(".paging").load("/ajax/page"+currentpage+".html");
	}	if(currentpage>1)
	{
	$(".prev").parent("li").removeClass("disabled");
	}
		else
		{$(".prev").parent("li").addClass("disabled");
	}if(currentpage>=maxpage)
{
$(".next").parent("li").addClass("disabled");
}
else
{
$(".next").parent("li").removeClass("disabled");
}
});

});



/*  live join subject page */
function LoadMore() {
    this.paginator = {};
    this.callbacks = {before:{}, after:{}};
}
LoadMore.prototype.getNextPage = function(buttonSelector) {
    if(!this.paginator[buttonSelector]) {
        this.paginator[buttonSelector] = 1;
    }

    this.paginator[buttonSelector]++;
    return this.paginator[buttonSelector];
}
LoadMore.prototype.curlyBracketsVars = function(url, params) {

    var curlyRe = /\{(.*?)}/g;
    url = url.replace(curlyRe, function() {
        if(!params[arguments[1]]) {
            return '{' + arguments[1] + '}';
        }
        return params[arguments[1]];
    });

    return url;
}
LoadMore.prototype.loadMoreButton = function(buttonSelector, eventName, appendToSelector, url, params, type, limit) {
    params['rnd'] = Math.random(); //to avoid cache

    params['limit'] = limit;
    var loadMoreObj = this;

    $(buttonSelector).bind(eventName ,function() {

        params['page'] = loadMoreObj.getNextPage(buttonSelector);

        $.ajax({
            url: jQuery.nano(url, params), //loadMoreObj.curlyBracketsVars(url, params) + '.json',
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
            } else {
                //Default action
                if(!data) {
                    $(buttonSelector).css('visibility', 'hidden');
                }
            }
            $(data).appendTo(appendToSelector);

            afterCallback = loadMoreObj.getAppendCallback(buttonSelector, 'after');
            if(afterCallback) {
                afterCallback( data );
            }
        });

     });

};
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

$(document).ready(function(){
    /* My Subject */

    //Scroll
    $('.my-subject-box').slimScroll({
        height: '155px',
        start: 'top',
        width: '100%',
        disableFadeOut: true
    });

    lmObj.loadMoreButton('.mysubject-more', 'click', '.subject-box', '/Home/getTeacherSubjects/{teacher_user_id}/{limit}/{page}', jsSettings, 'get', 3);
});

$(document).ready(function(){
    /* Upcoming lessons */

    //Scroll
    $('div.up-coming').slimScroll({
        height: '90px',
        start: 'top',
        width: '100%',
        disableFadeOut: true
    });

    lmObj.loadMoreButton('a.upcoming-more', 'click', 'ul.upcoming-more', '/Home/getUpcomingOpenLesson/{limit}/{page}', jsSettings, 'get', 3);
});

/* studentkit-student-page */

$(document).ready(function(){
    /* Reviews by students */
    // For Search Selectbox
    $(document).ready(function(){
        $('div.reviews-by-students').slimScroll({
            height: '132px',
            width: '100%',
            start: 'top'
        });

        lmObj.loadMoreButton('a.reviews-by-students', 'click', 'div.reviews-by-students', '/Home/getTeacherRatingByStudents/{teacher_user_id}/{limit}/{page}', jsSettings, 'get', 3);
    });
});



/* slim scroll */

$(document).ready(function(){
    function changeTime(spanId,val){
        document.getElementById(spanId).innerHTML=	val;
    }
    // For Search Selectbox
    $(document).ready(function(){
        $('.board-msg').slimScroll({
            height: '404px',
            alwaysVisible: false,
            start: 'bottom',
            wheelStep: 10
        });
        $(".more-btn1").click(function(){
            var ht=$(".temphtml").load("/ajax/more.html", function(response, status, xhr){;
                $('.board-msg').append(response);
                $(".board-msg").slimScroll({scroll: '50px' });
            });
        });

    });
});



$(document).ready(function(){
    function changeTime(spanId,val){
        document.getElementById(spanId).innerHTML=	val;
    }
    // For Search Selectbox
    $(document).ready(function(){
        $('.studnt-page-scorll1').slimScroll({
            height: '243px',
            alwaysVisible: false,
            start: 'bottom',
            wheelStep: 6
        });
        $("a.studnt-page-scorll-2").click(function(){
            $(".temphtml2").load("/ajax/more1.html", function(response, status, xhr) {
                $('.studnt-page-scorll1').append(response);
                $(".studnt-page-scorll1").slimScroll({scroll: '50px' });
            });

        });

    });
});
/* student subject page */

$(document).ready(function(){
	function changeTime(spanId,val){
			document.getElementById(spanId).innerHTML=	val;
        }
		// For Search Selectbox
	
			$('.teacherbox').slimScroll({
			  height: '525px',
			  alwaysVisible: false,
			  start: 'bottom',
			  wheelStep: 6
			});		
			$("a.teacher-more").click(function(){
				$(".mysubjectbox-temp").load("/ajax/teacher-profile.html", function(response, status, xhr) {
							$('.teacher-box2').append(response);
							$(".teacherbox").slimScroll({scroll: '50px' });												 
				 });
	
				
			});
  });

$(document).ready(function(){
	function changeTime(spanId,val){
			document.getElementById(spanId).innerHTML=	val;
        }
		// For Search Selectbox
		$(document).ready(function(){
			$('.scorllbox').slimScroll({
			  height: '300px',
			  alwaysVisible: false,
			  start: 'bottom',
			  wheelStep: 10
			});		
			$(".message-tm-more").click(function(){
				var ht=$(".temphtml").load("/ajax/more.html", function(response, status, xhr){;
				$('.scorllbox').append(response);
				$(".scorllbox").slimScroll({scroll: '50px' });
		       });
			});

        });						   
});

$(document).ready(function(){
	function changeTime(spanId,val){
			document.getElementById(spanId).innerHTML=	val;
        }
		// For Search Selectbox
		$(document).ready(function(){
			$('.message-tm-stu').slimScroll({
			  height: '300px',
			  alwaysVisible: false,
			  start: 'bottom',
			  wheelStep: 10
			});		
			$(".message-tm-more").click(function(){
				var ht=$(".temphtml").load("/ajax/more.html", function(response, status, xhr){;
				$('.message-tm-stu').append(response);
				$(".message-tm-stu").slimScroll({scroll: '50px' });
		       });
			});

        });						   
});


/* loadign */



$(document).ready(function(){

 hideLoading();
$(".upload-icon").click( function(){
$(".loadingbox").show();
var t=setTimeout(" hideLoading();",5000);

});

});
function hideLoading()
{
	$(".loadingbox").hide();
}