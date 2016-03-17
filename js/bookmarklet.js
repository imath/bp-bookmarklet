jQuery(document).ready( function($){
	var buddypress_div = $('#buddypress').html();
	var listImages = new Array();
	var nbreImage, currentImage, imgWidth;
	
	$('body').find( '#buddypress' ).remove();
	$('body').prepend( '<div id="buddypress">' + buddypress_div + '</div>' );
	
	$('#whats-new-post-in-box').append( $('#bkmklet-container').html() );
	$('#bkmklet-container').remove();
	
	
	function getUrlVars()
	{
	    var vars = [], hash;
	    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++)
	    {
	        hash = hashes[i].split('=');
	        vars.push(hash[0]);
	        vars[hash[0]] = hash[1];
	    }
	    return vars;
	}
	
	if( $.cookie( 'bp-message-type' ) != 'success' ) {
		
		$.fn.selectRange = function(start, end) {
	    	return this.each(function() {
	        	if(this.setSelectionRange) {
	            	this.focus();
	            	this.setSelectionRange(start, end);
	        	} else if(this.createTextRange) {
	            	var range = this.createTextRange();
	            	range.collapse(true);
	            	range.moveEnd('character', end);
	            	range.moveStart('character', start);
	            	range.select();
	        	}
	    	});
		};
		
		var urlvars = getUrlVars();
		
		copied = urlvars['copied'];

		if( typeof( copied ) == "undefined" )
			copied = bookmarklet_vars.copied_message;
		
		$("#whats-new").val( decodeURIComponent(copied) +' <a href="'+decodeURIComponent(urlvars['url'])+'">'+decodeURIComponent(urlvars['title'])+'</a>');

		var end = decodeURIComponent(copied).length; 

		$("#whats-new").selectRange(0, end);
		
		$("#whats-new-options").animate({
			height:'40px'
		});
		$("#whats-new-form textarea").animate({
			height:'50px'
		});
		$("#aw-whats-new-submit").prop("disabled", false);
		
		$('#bkmklet-image-cb').val( decodeURIComponent( urlvars['url'] ) );
		
		$( '#whats-new-form' ).on( 'click', '#bkmklet-image-cb', function(){
			if( $(this).attr('checked') ) {
				$.cookie( 'bp-bkmklet_url', $(this).val(), {path: '/'} );
				getImages( $(this).val() );
			} else {
				$.cookie( 'bp-bkmklet_url', '', {path: '/'} );
				$.cookie( 'bp-bkmklet_image_url', '', {path: '/'} );
				$( '#link-result' ).html('');
			}
				
		});
		
		$( document ).ajaxSuccess( function(event, xhr, settings ) {
			
			if( xhr.responseText[0] == "{" )
				return false;

			$.cookie( 'bp-bkmklet_image_url', '', {path: '/'} );
			$.cookie( 'bp-bkmklet_url', '', {path: '/'} );
			
			if( xhr.responseText[0] + xhr.responseText[1] == '-1' ) {
				$( '#whats-new-form' ).prepend( xhr.responseText.substr( 2, response.length ) );
				$( '#whats-new-form div.error' ).hide().fadeIn( 200 );
			} else {
				$( '#whats-new-form' ).prepend( '<div id="message" class="updated"><p>'+bookmarklet_vars.shared_message+'</p></div>' );
				$( '#link-result' ).html('');
				setTimeout('window.close();', 1000);
			}
		  	
		});
		
	} else {
		$.cookie( 'bp-bkmklet_image_url', '', {path: '/'} );
		$.cookie( 'bp-bkmklet_url', '', {path: '/'} );
		setTimeout('window.close();', 1000);
	}
	
	$('#buddypress').on('click', '#bkmklet_next', function(){
		
		if( currentImage + 1 < nbreImage ) {
			currentImage += 1;
			$('.link-image-container ul li img').attr('src', listImages[currentImage]);
			$('#bkmklet-image-url').val( listImages[currentImage] );
			$.cookie( 'bp-bkmklet_image_url', listImages[currentImage], {path: '/'} );
			$('.link-image-container ul li').attr('id', 'img-'+currentImage);
			$('#bkmklet_prev').removeClass('bkmklet-disabled');
		} else {
			$(this).addClass('bkmklet-disabled');
		}
		return false;
	});
	
	$('#buddypress').on('click', '#bkmklet_prev', function(){
		
		if( currentImage - 1 >= 0 ) {
			currentImage -= 1;
			$('.link-image-container ul li img').attr('src', listImages[currentImage]);
			$('#bkmklet-image-url').val( listImages[currentImage] );
			$.cookie( 'bp-bkmklet_image_url', listImages[currentImage], {path: '/'} );
			$('.link-image-container ul li').attr('id', 'img-'+currentImage);
			$('#bkmklet_next').removeClass('bkmklet-disabled');
			
		} else {
			$(this).addClass('bkmklet-disabled');
		}
		return false;
	});
	
	function getImages( link ) {
		
		$('#link-result').html( '<div class="bkmklet-loading">'+bookmarklet_vars.loading_message+'</div>' );
		window.resizeTo($('body').width(),$('#buddypress').height()+80);
		
		var data = {
	      action: 'bkmklet_get_images',
	      url: link
	    };
	
		$.post(ajaxurl, data, function(response) {
			
			if( response['http_request_failed'] ){
				$('#link-result').html( response['http_request_failed'] );
				return false;
			}
			if( response['error'] ) {
				$('#link-result').html( response['error'] );
				return false;
			}
			
			imgResult='';
			imgHeight = 150;
			nbreImage = Number( response.total_images );
			listImages = new Array();
			
			if( nbreImage > 0 ) {
				imgResult = '<ul style="width:auto;height:'+imgHeight+';overflow:hidden;float-left">';
				
				for(i = 0; i < nbreImage ; i++ ) {
					listImages.push(response.images[i]['img']);
				}
				imgResult += '<li id="img-0"><img src="'+response.images[0]['img']+'" height="'+imgHeight+'px"></li>';
				currentImage = 0;
				imgResult += '</ul>';
			}
			
	      
			if( nbreImage < 1 ) {
				$('#link-result').html( bookmarklet_vars.no_image );
				return false;
			}
			
			htmlResult = '<table class="link-container"><tr>'+
			             '<td class="link-image-container" style="height:'+Number(imgHeight+10)+'px">' + imgResult +'</td><td>'+
						 '<p>'+ bookmarklet_vars.arrows_message +'</p>'+
						 '<p><a href="#" id="bkmklet_prev" class="bkmklet-disabled"></a> <a href="#" id="bkmklet_next"></a></p></td>'+
						 '</tr></table>';
			$('#link-result').html( htmlResult );
			$('#bkmklet-image-url').val( response.images[0]['img'] );
			$.cookie( 'bp-bkmklet_image_url', response.images[0]['img'], {path: '/'} );
			window.resizeTo($('body').width(),$('#buddypress').height()+80);
			
			return false;
	    }, 'json' );
	}
	
});
