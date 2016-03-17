<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Parses the page added as a link and return a json array with the src attributes of images
 *
 * @uses WP_Http::request() to get the best available method to load the html source of the page
 * @uses bkmklet_extract_tags() to extract the images
 * @return string the json array
 */
function bkmklet_get_image_data() {
	
	if( empty( $_POST['url'] ) ) {
		echo json_encode( array( 'http_request_failed' => __( 'OOops, you forgot to add the url', 'bp-bookmarklet' ) ) );
		die();
	}
	
	$url_data = array();
	$url = $_POST['url'];
	$base_url = substr( $url, 0, strpos($url, "/",8) );
	$relative_url = substr( $url, 0, strrpos( $url, "/" )+1) ;
	
	$request = new WP_Http;
	$result = $request->request( $url );
	if( !empty( $result->errors ) ) {
		$url_data['error'] = __( 'OOps, something went wrong :(', 'bp-bookmarklet' ) ;
		echo json_encode( $url_data );
		die();
	}
	
	$html_string = $result['body'];
	
	/* base */
	$base_override = false; 
	$base_regex = '/<base[^>]*'.'href=[\"|\'](.*)[\"|\']/Ui';
	preg_match_all($base_regex, $html_string, $base_match, PREG_PATTERN_ORDER);
	if( !empty( $base_match[1][0] ) && strlen( $base_match[1][0] ) > 0)
	{
		$base_url = $base_match[1][0];
		$base_override = true; 
	}
	
	/* images */
	$images_parse = bkmklet_extract_tags( $html_string, 'img' );
	$images = array();
	
	for ($i=0;$i<=sizeof($images_parse);$i++)
	{
		$img = trim(@$images_parse[$i]['attributes']['src']);
		
		$width = '';
		if( !empty( $images_parse[$i]['attributes']['width'] ) )
			$width = preg_replace("/[^0-9.]/", '', $images_parse[$i]['attributes']['width']);
		
		$height = '';
		if( !empty( $images_parse[$i]['attributes']['height'] ) )
			$height = preg_replace("/[^0-9.]/", '', $images_parse[$i]['attributes']['height']);

		$ext = trim(pathinfo($img, PATHINFO_EXTENSION));

		if($img && $ext != 'gif') 
		{
			if (substr($img,0,7) == 'http://')
				;
			else	if (substr($img,0,1) == '/' || $base_override)
				$img = $base_url . $img;
			else 
				$img = $relative_url . $img;

			if ($width == '' && $height == '')
			{
				$details = @getimagesize($img);

				if(is_array($details))
				{
					list($width, $height, $type, $attr) = $details;
				} 
			}
			$width = intval($width);
			$height = intval($height);


			if ($width > 199 || $height > 199 )
			{
				if (
					(($width > 0 && $height > 0 && (($width / $height) < 3) && (($width / $height) > .2)) 
						|| ($width > 0 && $height == 0 && $width < 700) 
						|| ($width == 0 && $height > 0 && $height < 700)
					) 
					&& strpos($img, 'logo') === false )
				{
					$images[] = array("img" => $img, "width" => $width, "height" => $height, 'area' =>  ($width * $height),'offset' => $images_parse[$i]['offset']);
				}
			}

		}
	}
	$url_data['images'] = array_values(($images));
	$url_data['total_images'] = count($url_data['images']);

	echo json_encode( $url_data );
	
	die();
}

add_action( 'wp_ajax_bkmklet_get_images', 'bkmklet_get_image_data' );


/**
 * Extract tags form the html string and returns them in an array
 *
 * Many thanks to casparro for his script 
 * http://www.redsunsoft.com/2011/01/parse-link-like-facebook-with-jquery-and-php/
 *
 * @param string $html 
 * @param string $tag 
 * @param string $selfclosing 
 * @param string $return_the_entire_tag 
 * @param string $charset 
 * @return array the tags
 */
function bkmklet_extract_tags( $html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1' ){
 
	if ( is_array($tag) ){
		$tag = implode('|', $tag);
	}
 
	//If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
	//by checking against a list of known self-closing tags.
	$selfclosing_tags = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta', 'col', 'param' );
	if ( is_null($selfclosing) ){
		$selfclosing = in_array( $tag, $selfclosing_tags );
	}
 
	//The regexp is different for normal and self-closing tags because I can't figure out 
	//how to make a sufficiently robust unified one.
	if ( $selfclosing ){
		$tag_pattern = 
			'@<(?P<tag>'.$tag.')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*/?>					# /> or just >, being lenient here 
			@xsi';
	} else {
		$tag_pattern = 
			'@<(?P<tag>'.$tag.')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*>					# >
			(?P<contents>.*?)			# tag contents
			</(?P=tag)>				# the closing </tag>
			@xsi';
	}
 
	$attribute_pattern = 
		'@
		(?P<name>\w+)							# attribute name
		\s*=\s*
		(
			(?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)	# a quoted value
			|							# or
			(?P<value_unquoted>[^\s"\']+?)(?:\s+|$)			# an unquoted value (terminated by whitespace or EOF) 
		)
		@xsi';
 
	//Find all tags 
	if ( !preg_match_all($tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ){
		//Return an empty array if we didn't find anything
		return array();
	}
 
	$tags = array();
	foreach ($matches as $match){
 
		//Parse tag attributes, if any
		$attributes = array();
		if ( !empty($match['attributes'][0]) ){ 
 
			if ( preg_match_all( $attribute_pattern, $match['attributes'][0], $attribute_data, PREG_SET_ORDER ) ){
				//Turn the attribute data into a name->value array
				foreach($attribute_data as $attr){
					if( !empty($attr['value_quoted']) ){
						$value = $attr['value_quoted'];
					} else if( !empty($attr['value_unquoted']) ){
						$value = $attr['value_unquoted'];
					} else {
						$value = '';
					}
 
					//Passing the value through html_entity_decode is handy when you want
					//to extract link URLs or something like that. You might want to remove
					//or modify this call if it doesn't fit your situation.
					$value = html_entity_decode( $value, ENT_QUOTES, $charset );
 
					$attributes[$attr['name']] = $value;
				}
			}
 
		}
 
		$tag = array(
			'tag_name' => $match['tag'][0],
			'offset' => $match[0][1], 
			'contents' => !empty($match['contents'])?$match['contents'][0]:'', //empty for self-closing tags
			'attributes' => $attributes, 
		);
		if ( $return_the_entire_tag ){
			$tag['full_tag'] = $match[0][0]; 			
		}
 
		$tags[] = $tag;
	}
 
	return $tags;
}