<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2022 ThemePunch
 */

namespace Nwdthemes\Revslider\Model\Revslider\Admin\Includes;

use \Nwdthemes\Revslider\Helper\Framework;
use \Nwdthemes\Revslider\Helper\Query;
use \Nwdthemes\Revslider\Model\FrameworkAdapter as FA;
use \Nwdthemes\Revslider\Model\Revslider\Admin\Includes\RevSliderPluginUpdate;
use \Nwdthemes\Revslider\Model\Revslider\Front\RevSliderFront;
use \Nwdthemes\Revslider\Model\Revslider\RevSliderFunctions;
use \Nwdthemes\Revslider\Model\Revslider\RevSliderObjectLibrary;
use \Nwdthemes\Revslider\Model\Revslider\RevSliderSlide;
use \Nwdthemes\Revslider\Model\Revslider\RevSliderSlider;

class RevSliderFunctionsAdmin extends RevSliderFunctions {

	/**
	 * get the full object of:
	 * +- Slider Templates
	 * +- Created Slider
	 * +- Object Library Images
	 * - Object Library Videos
	 * +- SVG
	 * +- Font Icons
	 * - layers
	 **/
	public function get_full_library($include = array('all'), $tmp_slide_uid = array(), $refresh_from_server = false, $get_static_slide = false){
		$include	= (array)$include;
		$template	= new RevSliderTemplate();
		$library	= new RevSliderObjectLibrary();
		$slide		= new RevSliderSlide();
		$object		= array();
		$tmp_slide_uid = ($tmp_slide_uid !== false) ? (array)$tmp_slide_uid : array();

		if($refresh_from_server){
			if(in_array('all', $include) || in_array('moduletemplates', $include)){ //refresh template list from server
				$template->_get_template_list(true);
				if(!isset($object['moduletemplates'])) $object['moduletemplates'] = array();
				$object['moduletemplates']['tags'] = $template->get_template_categories();
				asort($object['moduletemplates']['tags']);
			}
			if(in_array('all', $include) || in_array('layers', $include) || in_array('videos', $include) || in_array('images', $include) || in_array('objects', $include)){ //refresh object list from server
				$library->_get_list(true);
			}
			if(in_array('all', $include) || in_array('layers', $include)){ //refresh object list from server
				if(!isset($object['layers'])) $object['layers'] = array();
				$object['layers']['tags'] = $library->get_objects_categories('4');
				asort($object['layers']['tags']);
			}
			if(in_array('all', $include) || in_array('videos', $include)){ //refresh object list from server
				if(!isset($object['videos'])) $object['videos'] = array();
				$object['videos']['tags'] = $library->get_objects_categories('3');
				asort($object['videos']['tags']);
			}
			if(in_array('all', $include) || in_array('images', $include)){ //refresh object list from server
				if(!isset($object['images'])) $object['images'] = array();
				$object['images']['tags'] = $library->get_objects_categories('2');
				asort($object['images']['tags']);
			}
			if(in_array('all', $include) || in_array('objects', $include)){ //refresh object list from server
				if(!isset($object['objects'])) $object['objects'] = array();
				$object['objects']['tags'] = $library->get_objects_categories('1');
				asort($object['objects']['tags']);
			}
			$object = FA::apply_filters('revslider_get_full_library_refresh', $object, $include, $tmp_slide_uid, $refresh_from_server, $get_static_slide, $this);
		}

		if(in_array('moduletemplates', $include) || in_array('all', $include)){
			if(!isset($object['moduletemplates'])) $object['moduletemplates'] = array();
			$object['moduletemplates']['items']	= $template->get_tp_template_sliders_for_library($refresh_from_server);
		}
		if(in_array('moduletemplateslides', $include) || in_array('all', $include)){
			if(!isset($object['moduletemplateslides'])) $object['moduletemplateslides'] = array();
			$object['moduletemplateslides']['items'] = $template->get_tp_template_slides_for_library($tmp_slide_uid);
		}
		if(in_array('modules', $include) || in_array('all', $include)){
			if(!isset($object['modules'])) $object['modules'] = array();
			$object['modules']['items'] = $this->get_slider_overview();
		}
		if(in_array('moduleslides', $include) || in_array('all', $include)){
			if(!isset($object['moduleslides'])) $object['moduleslides'] = array();
			$object['moduleslides']['items'] = $slide->get_slides_for_library($tmp_slide_uid, $get_static_slide);
		}
		if(in_array('svgs', $include) || in_array('all', $include)){
			if(!isset($object['svgs'])) $object['svgs'] = array();
			$object['svgs']['items'] = $library->get_svg_sets_full();
		}
		if(in_array('svgcustom', $include) || in_array('all', $include)){
			if(!isset($object['svgcustom'])) $object['svgcustom'] = array();
			$object['svgcustom']['items'] = $library->get_custom_svgs();
		}
		if(in_array('fonticons', $include) || in_array('all', $include)){
			if(!isset($object['fonticons'])) $object['fonticons'] = array();
			$object['fonticons']['items'] = $library->get_font_icons();
		}
		if(in_array('layers', $include) || in_array('all', $include)){
			if(!isset($object['layers'])) $object['layers'] = array();
			$object['layers']['items'] = $library->load_objects('4');
		}
		if(in_array('videos', $include) || in_array('all', $include)){
			if(!isset($object['videos'])) $object['videos'] = array();
			$object['videos']['items'] = $library->load_objects('3');
		}
		if(in_array('images', $include) || in_array('all', $include)){
			if(!isset($object['images'])) $object['images'] = array();
			$object['images']['items'] = $library->load_objects('2');
		}
		if(in_array('objects', $include) || in_array('all', $include)){
			if(!isset($object['objects'])) $object['objects'] = array();
			$object['objects']['items'] = $library->load_objects('1');
		}
		$object = FA::apply_filters('revslider_get_full_library', $object, $include, $tmp_slide_uid, $refresh_from_server, $get_static_slide, $this);

		return $object;
	}


	/**
	 * get the short library with categories and how many elements exist
	 **/
    public function get_short_library($sliders = false){

		$template = new RevSliderTemplate();
		$library = new RevSliderObjectLibrary();

        $sliders = ($sliders === false) ? $this->get_slider_overview() : $sliders;

		$slider_cat = array();
		if(!empty($sliders)){
			foreach($sliders as $slider){
				$tags = $this->get_val($slider, 'tags', array());
				if(!empty($tags)){
					foreach($tags as $tag){
						if(trim($tag) !== '' && !isset($slider_cat[$tag])) $slider_cat[$tag] = ucwords($tag);
					}
				}
			}
		}

		$svg_cat = $library->get_svg_categories();
		$oc	= $library->get_objects_categories('1');
		$oc2 = $library->get_objects_categories('2');
		$oc3 = $library->get_objects_categories('3');
		$oc4 = $library->get_objects_categories('4');
		$t_cat = $template->get_template_categories();
		$font_cat = $library->get_font_tags();
		$custom = $library->get_custom_tags();

		$wpi = array('jpg' => 'jpg', 'png' => 'png');
		$wpv = array('mpeg' => 'mpeg', 'mp4' => 'mp4', 'ogv' => 'ogv');

		asort($wpi);
		asort($wpv);
		asort($oc);
		asort($t_cat);
		asort($slider_cat);
		asort($svg_cat);
		asort($font_cat);

		$tags = array(
			'moduletemplates' => array('tags' => $t_cat),
			'modules'	=> array('tags' => $slider_cat),
			'svgs'		=> array('tags' => $svg_cat),
			'fonticons'	=> array('tags' => $font_cat),
			'layers'	=> array('tags' => $oc4),
			'videos'	=> array('tags' => $oc3),
			'images'	=> array('tags' => $oc2),
			'objects'	=> array('tags' => $oc)
		);

		if(!empty($custom)){
			foreach($custom as $tag_name => $tag_value){
				$tags[$tag_name] = array('tags' => $tag_value);
			}
		}

		return FA::apply_filters('revslider_get_short_library', $tags, $library, $this);
	}


	/**
	 * Get Sliders data for the overview page
	 **/
	public function get_slider_overview(){
        Framework::$rs_do_init_action = false;

		$rs_slider	= new RevSliderSlider();
        $rs_slide    = new RevSliderSlide();
		$sliders	= $rs_slider->get_sliders(false);
		$rs_folder	= new RevSliderFolder();
		$folders	= $rs_folder->get_folders();

		$sliders 	= array_merge($sliders, $folders);
		$data		= array();
		if(!empty($sliders)){
            $slider_list = array();
			foreach($sliders as $slider){
                $slider_list[] = $slider->get_id();
            }

            $_slides_raw = $rs_slide->get_all_slides_raw($slider_list);
            $slides_raw = $this->get_val($_slides_raw, 'first_slides', array());
            $slides_ids = $this->get_val($_slides_raw, 'slide_ids', array());

            foreach($sliders as $k => $slider){
                $slide_ids = array();
                $slides = array();
                $sid = $slider->get_id();
                foreach($slides_raw as $s => $r){
                    if($r->get_slider_id() !== $sid) continue;

                    foreach($slides_ids as $_s => $_sv){
                        if($this->get_val($_sv, 'slider_id') === $sid){
                            $slide_ids[] = $this->get_val($_sv, 'id');
                            unset($slides_ids[$_s]);
                        }
                    }
                    $slides[] = $r;
                    unset($slides_raw[$s]);
                }
                if(empty($slide_ids)) $slide_ids = false;

                $slides = (empty($slides)) ? false : $slides;

				$slider->init_layer = false;
                $data[] = $slider->get_overview_data(false, $slides, $slide_ids);
                unset($sliders[$k]);
            }
		}

        Framework::$rs_do_init_action = true;

		return $data;
	}


	/**
	 * insert custom animations
	 * @before: RevSliderOperations::insertCustomAnim();
	 */
	public function insert_animation($animation, $type){
		$handle = $this->get_val($animation, 'name', false);
		$result = false;

		if($handle !== false && trim($handle) !== ''){
			$wpdb = FA::getQueryHelper();

			//check if handle exists
			$arr = array(
				'handle'	=> $this->get_val($animation, 'name'),
				'params'	=> json_encode($animation),
				'settings'	=> $type
			);

			$result = $wpdb->insert($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, $arr);
		}

		return ($result) ? $wpdb->insert_id : $result;
	}


	/**
	 * update custom animations
	 * @before: RevSliderOperations::updateCustomAnim();
	 */
	public function update_animation($animation_id, $animation, $type){
		$wpdb = FA::getQueryHelper();

		$arr = array(
			'handle'	=> $this->get_val($animation, 'name'),
			'params'	=> json_encode($animation),
			'settings'	=> $type
		);

		$result = $wpdb->update($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, $arr, array('id' => $animation_id));

		return ($result) ? $animation_id : $result;
	}


	/**
	 * delete custom animations
	 * @before: RevSliderOperations::deleteCustomAnim();
     * @param int $animation_id
	 */
	public function delete_animation($animation_id){
		$wpdb = FA::getQueryHelper();

		$result = $wpdb->delete($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, array('id' => $animation_id));

		return $result;
	}


	/**
	 * @since: 5.3.0
	 * create a page with revslider shortcodes included
	 * @before: RevSliderOperations::create_slider_page();
     * @param array $added
     * @param array $modals
     * @param array $additions
     **/
	public static function create_slider_page($added, $modals = array(), $additions = array()){
		$new_page_id = 0;

		if(!is_array($added)) return FA::apply_filters('revslider_create_slider_page', $new_page_id, $added);

		$content = '';
		$page_id = FA::get_option('rs_import_page_id', 1);

		//get alias of all new Sliders that got created and add them as a shortcode onto a page
		foreach($added as $sid){
			$slider = new RevSliderSlider();
			$slider->init_by_id($sid);
			$alias = $slider->get_alias();
			if($alias !== ''){
				$usage		= (in_array($sid, $modals, true)) ? ' usage="modal"' : '';
				$addition	= (isset($additions[$sid])) ? ' ' . $additions[$sid] : '';
				if(strpos($addition, 'usage=\"modal\"') !== false) $usage = ''; //remove as not needed two times
				$content .= '{{block class="Nwdthemes\Revslider\Block\Revslider" alias="'.$alias.'"'.$usage.$addition.'}}' . "\n"; //this way we will reorder as last comes first
			}
		}

		if($content !== ''){

            $_title = __('Revolution Slider Page') . ' - ' . FA::sanitize_title($slider->getTitle());
            $_id = 'revslider-' . $page_id . '-' . time() . '-' . FA::sanitize_title_with_dashes($alias);

            $newPageId = FA::createPage($_id, $_title, $content);

            if ($newPageId) {
                $page_id++;
                FA::update_option('rs_import_page_id', $page_id);
            }
		}

		return FA::apply_filters('revslider_create_slider_page', $newPageId, $added);
	}

	/**
	 * add notices from ThemePunch
	 * @since: 4.6.8
	 * @return array
	 */
	public function add_notices(){
		$_n = array();
		$notices = (array)FA::get_option('revslider-notices', false);
        $rs_valid = $this->_truefalse(FA::get_option('revslider-valid', 'false'));

		if(!empty($notices) && is_array($notices)){
			$n_discarted = FA::get_option('revslider-notices-dc', array());

			foreach($notices as $notice) if ($notice) {
                if(in_array($notice->code, $n_discarted)) continue;
                if(isset($notice->version) && version_compare($notice->version, Framework::RS_REVISION, '<=')) continue;
                if(isset($notice->registered)){ //if this is set, only show the notice if the plugin state is the same
                    $registered = $this->_truefalse($notice->registered);
                    if($registered !== $rs_valid) continue;
                }
                if(isset($notice->show_until) && $notice->show_until !== '0000-00-00 00:00:00'){
                    if(strtotime($notice->show_until) < time()) continue;
                }

                $_n[] = $notice;
			}
		}

		//push whatever notices we might need
		return $_n;
	}

	/**
	 * get basic v5 Slider data
	 **/
	public function get_v5_slider_data(){
		$wpdb = FA::getQueryHelper();

		$sliders	= array();
		$do_order	= 'id';
		$direction	= 'ASC';

		$slider_data = $wpdb->get_results($wpdb->prepare("SELECT `id`, `title`, `alias`, `type` FROM ".$wpdb->prefix . RevSliderFront::TABLE_SLIDER."_bkp ORDER BY %s %s", array($do_order, $direction)), Query::ARRAY_A);

		if(!empty($slider_data)){
			foreach($slider_data as $data){
				if($this->get_val($data, 'type') == 'template') continue;

				$sliders[] = $data;
			}
		}

		return $sliders;
	}

	/**
	 * get basic v5 Slider data
	 **/
	public function reimport_v5_slider($id){
		$wpdb = FA::getQueryHelper();

		$done = false;

		$slider_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_SLIDER."_bkp WHERE `id` = %s", $id), Query::ARRAY_A);

		if(!empty($slider_data)){
			$slides_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_SLIDES."_bkp WHERE `slider_id` = %s", $id), Query::ARRAY_A);
			$static_slide_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_STATIC_SLIDES."_bkp WHERE `slider_id` = %s", $id), Query::ARRAY_A);

			if(!empty($slides_data)){
				//check if the ID's exist in the new tables, if yes overwrite, if not create
				$slider_v6 = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_SLIDER." WHERE `id` = %s", $id), Query::ARRAY_A);
				unset($slider_data['id']);
				if(!empty($slider_v6)){
					/**
					 * push the old data to the already imported Slider
					 **/
					$result = $wpdb->update($wpdb->prefix . RevSliderFront::TABLE_SLIDER, $slider_data, array('id' => $id));
				}else{
					$result	= $wpdb->insert($wpdb->prefix . RevSliderFront::TABLE_SLIDER, $slider_data);
					$id		= ($result) ? $wpdb->insert_id : false;
				}
				if($id !== false){
					foreach($slides_data as $k => $slide_data){
						$slide_data['slider_id'] = $id;
						$slide_v6 = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_SLIDES." WHERE `id` = %s", $slide_data['id']), Query::ARRAY_A);
						$slide_id = $slide_data['id'];
						unset($slide_data['id']);
						if(!empty($slide_v6)){
							$result = $wpdb->update($wpdb->prefix . RevSliderFront::TABLE_SLIDES, $slide_data, array('id' => $slide_id));
						}else{
							$result	= $wpdb->insert($wpdb->prefix . RevSliderFront::TABLE_SLIDES, $slide_data);
						}
					}
					if(!empty($static_slide_data)){
						$static_slide_data['slider_id'] = $id;
						$slide_v6 = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . RevSliderFront::TABLE_STATIC_SLIDES." WHERE `id` = %s", $static_slide_data['id']), Query::ARRAY_A);
						$slide_id = $static_slide_data['id'];
						unset($static_slide_data['id']);
						if(!empty($slide_v6)){
							$result = $wpdb->update($wpdb->prefix . RevSliderFront::TABLE_STATIC_SLIDES, $static_slide_data, array('id' => $slide_id));
						}else{
							$result	= $wpdb->insert($wpdb->prefix . RevSliderFront::TABLE_STATIC_SLIDES, $static_slide_data);
						}
					}

					$slider = new RevSliderSlider();
					$slider->init_by_id($id);

					$upd = new RevSliderPluginUpdate();

					$upd->upgrade_slider_to_latest($slider);
					$done = true;
				}
			}
		}

		return $done;
	}

	/**
	 * returns an object of current system values
	 **/
	public function get_system_requirements(){

		$wpdb = FA::getQueryHelper();
        
		$dir	= FA::wp_upload_dir();
		$basedir = $this->get_val($dir, 'basedir').'/';
		$ml		= ini_get('memory_limit');
		$mlb	= FA::wp_convert_hr_to_bytes($ml);
		$umf	= ini_get('upload_max_filesize');
		$umfb	= FA::wp_convert_hr_to_bytes($umf);
		$pms	= ini_get('post_max_size');
		$pmsb	= FA::wp_convert_hr_to_bytes($pms);
		$map	= $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet';");
		$map	= $this->get_val($map, 'Value', 0);


		$mlg  = ($mlb >= 268435456) ? true : false;
		$umfg = ($umfb >= 33554432) ? true : false;
		$pmsg = ($pmsb >= 33554432) ? true : false;
		$mapg = ($map >= 16777216) ? true : false;

		return array(
			'memory_limit' => array(
				'has' => FA::size_format($mlb),
				'min' => FA::size_format(268435456),
				'good'=> $mlg
			),
			'upload_max_filesize' => array(
				'has' => FA::size_format($umfb),
				'min' => FA::size_format(33554432),
				'good'=> $umfg
			),
			'post_max_size' => array(
				'has' => FA::size_format($pmsb),
				'min' => FA::size_format(33554432),
				'good'=> $pmsg
			),
			'max_allowed_packet' => array(
				'has' => FA::size_format($map),
				'min' => FA::size_format(16777216),
				'good'=> $mapg
			),
			'upload_folder_writable'	=> FA::wp_is_writable($basedir),
			'zlib_enabled'				=> function_exists('gzcompress') && function_exists('gzuncompress'),
			'object_library_writable'	=> FA::wp_image_editor_supports(array('methods' => array('resize', 'save'))),
			'server_connect'			=> FA::get_option('revslider-connection', false),
		);
	}

	/**
	 * import a media file uploaded through the browser to the media library
	 **/
	public function import_upload_media(){

		$wp_filesystem = FA::getFilesystemHelper();

		$import_file = $this->get_val($_FILES, 'import_file');
		$error		 = $this->get_val($import_file, 'error');
		$return		 = array('error' => __('File not found'));

		switch($error){
			case UPLOAD_ERR_NO_FILE:
				return array('error' => __('No file sent'));
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return array('error' => __('Exceeded filesize limit'));
			default:
			break;
		}

		$path = $this->get_val($import_file, 'tmp_name');
		if(isset($path['error'])) return array('error' => $path['error']);

		if(file_exists($path) == false) return array('error' => __('File not found'));

		$file_mime = mime_content_type($path);
		$allow = array(
			'jpg|jpeg|jpe'	=> 'image/jpeg',
			'gif'			=> 'image/gif',
			'png'			=> 'image/png',
			'bmp'			=> 'image/bmp',
			'mpeg|mpg|mpe'	=> 'video/mpeg',
			'mp4|m4v'		=> 'video/mp4',
			'ogv'			=> 'video/ogg',
			'webm'			=> 'video/webm'
		);

		if(!in_array($file_mime, $allow)) return array('error' => __('Slider Revolution doesn\'t allow this filetype'));

        $file_name = basename($this->get_val($import_file, 'name'));
        $new_path = FA::getImagesHelper()->imageBaseDir() . DIRECTORY_SEPARATOR . $file_name;
		$i = 0;
		while(file_exists($new_path)){
			$i++;
			$new_path = FA::getImagesHelper()->imageBaseDir() . DIRECTORY_SEPARATOR . $i . '-' . $file_name;
		}

		if(move_uploaded_file($path, $new_path)){

			$imageId = FA::getImagesHelper()->get_image_id_by_url($new_path);
			if ($imageData = FA::wp_get_attachment_image_src($imageId, 'full')) {
				$url = $this->get_val($imageData, 0, '');
				$width	= $this->get_val($imageData, 1, '');
				$height	= $this->get_val($imageData, 2, '');
				$return = array(
					'error' => false,
					'id' => $imageId,
					'path' => $url,
					'width' => $width,
					'height' => $height
				);
			}

		}

		return $return;
	}

	public function sort_by_slide_order($a, $b) {
		return $a['slide_order'] - $b['slide_order'];
	}


	/**
	 * Create Multilanguage for JavaScript
	 */
	public function get_javascript_multilanguage(){
		$lang = array(
            'up' => __('Up'),
            'down' => __('Down'),
            'left' => __('Left'),
            'right' => __('Right'),
            'horizontal' => __('Horizontal'),
            'vertical' => __('Vertical'),
            'reversed' => __('Reverse'),
			'previewnotworking' => __('The preview could not be loaded due to some error'),
			'checksystemnotworking' => __('Server connection issues, contact your hosting provider for further assistance'),
			'editskins' => __('Edit Skin List'),
			'globalcoloractive' => __('Color Skin Active'),
			'corejs' => __('Core JavaScript'),
			'corecss' => __('Core CSS'),
			'coretools' => __('Core Tools (GreenSock & Co)'),
			'enablecompression' => __('Enable Server Compression'),
			'notduringinsetmode' => __('Resize and Drag is not available if Layer Size set to Inset'),
			'insetrequirements' => __('Move Layer into a Group and set Position to Absolute before selecting Full Inset'),
			'noservercompression' => __('Not Available, read FAQ'),
			'servercompression' => __('Serverside Compression'),
			'sizeafteroptim' => __('Size after Optimization'),
			'chgimgsizesrc' => __('Change Image Size or Src'),
			'pickandim' => __('Pick another Dimension'),
			'optimize' => __('Optimize'),
			'savechanges' => __('Save Changes'),
			'applychanges' => __('Apply Changes'),
			'suggestion' => __('Suggestion'),
			'toosmall' => __('Too Small'),
			'standard1x' => __('Standard (1x)'),
			'retina2x' => __('Retina (2x)'),
			'oversized' => __('Oversized'),
			'quality' => __('Quality'),
			'file' => __('File'),
			'resize' => __('Resize'),
			'lowquality' => __('Optimized (Low Quality)'),
			'notretinaready' => __('Not Retina Ready'),
			'element' => __('Element'),
			'calculating' => __('Calculating...'),
			'filesize' => __('File Size'),
			'dimension' => __('Dimension'),
			'dimensions' => __('Dimensions'),
			'optimization' => __('Optimization'),
			'optimized' => __('Optimized'),
			'smartresize' => __('Smart Resize'),
			'optimal' => __('Optimal'),
			'recommended' => __('Recommended'),
			'hrecommended' => __('Highly Recommended'),
			'optimizertitel' => __('File Size Optimizer'),
			'loadedmediafiles' => __('Loaded Media Files'),
			'loadedmediainfo' => __('Optimize to save up to '),
			'optselection' => __('Optimize Selection'),
			'visibility' => __('Visibility'),
			'layers' => __('Layers'),
			'videoid' => __('Video ID'),
			'youtubeid' => __('YouTube ID'),
			'vimeoid' => __('Vimeo ID'),
			'poster' => __('Poster'),
			'youtubeposter' => __('YouTube Poster'),
			'vimeoposter' => __('Vimeo Poster'),
			'postersource' => __('Poster Image'),
			'medialibrary' => __('Media Library'),
			'objectlibrary' => __('Object Library'),
			'videosource' => __('Video Source'),
			'imagesource' => __('Image Source'),
			'extimagesource' => __('External Image Source'),
			'mediasrcimage' => __('Image Based'),
			'mediasrcext' => __('External Image'),
			'mediasrcsolid' => __('Background Color'),
			'mediasrctrans' => __('Transparent'),
			'please_wait_a_moment' => __('Please Wait a Moment'),
			'backgrounds' => __('Backgrounds'),
			'name' => __('Name'),
			'colorpicker' => __('Color Picker'),
			'savecontent' => __('Save Content'),
			'modulbackground' => __('Module Background'),
			'wrappingtag' => __('Wrapping Tag'),
			'tag' => __('Tag'),
			'content' => __('Content'),
			'nolayerstoedit' => __('No Layers to Edit'),
			'layermedia' => __('Layer Media'),
			'oppps' => __('Ooppps....'),
			'no_nav_changes_done' => __('None of the Settings changed. There is Nothing to Save'),
			'no_preset_name' => __('Enter Preset Name to Save or Delete'),
			'customlayergrid_size_title' => __('Custom Size is currently Disabled'),
			'customlayergrid_size_content' => __('The Current Size is set to calculate the Layer grid sizes Automatically.<br>Do you want to continue with Custom Sizes or do you want to keep the Automatically generated sizes ?'),
			'customlayergrid_answer_a' => __('Keep Auto Sizes'),
			'customlayergrid_answer_b' => __('Use Custom Sizes'),
			'removinglayer_title' => __('What should happen Next?'),
			'removinglayer_attention' => __('Need Attention by removing'),
			'removinglayer_content' => __('Where do you want to move the Inherited Layers?'),
			'dragAndDropFile' => __('Drag & Drop Import File'),
			'or' => __('or'),
			'clickToChoose' => __('Click to Choose'),
			'embed' => __('Embed'),
			'export' => __('Export'),
			'delete' => __('Delete'),
			'duplicate' => __('Duplicate'),
			'preview' => __('Preview'),
			'tags' => __('Tags'),
			'folders' => __('Folder'),
			'rename' => __('Rename'),
			'root' => __('Root Level'),
			'addcategory' => __('Add Category'),
			'show' => __('Show'),
			'perpage' => __('Per Page'),
			'convertedlayer' => __('Layer converted Successfully'),
			'layerloopdisabledduetimeline' => __('Layer Loop Effect disabled'),
			'layerbleedsout' => __('<b>Layer width bleeds out of Grid:</b><br>-Auto Layer width has been removed<br>-Line Break set to Content Based'),
			'noMultipleSelectionOfLayers' => __('Multiple Layerselection not Supported<br>in Animation Mode'),
			'closeNews' => __('Close News'),
			'copyrightandlicenseinfo' => __('&copy; Copyright & License Info'),
			'registered' => __('Registered'),
			'notRegisteredNow' => __('Unregistered'),
			'dismissmessages' => __('Dismiss Messages'),
			'someAddonnewVersionAvailable' => __('Some AddOns have new versions available'),
			'newVersionAvailable' => __('New Version Available. Please Update'),
			'pluginsmustbeupdated' => __('Plugin Outdated. Please Update'),
			'addonsmustbeupdated' => __('AddOns Outdated. Please Update'),
			'notRegistered' => __('Plugin is not Registered'),
			'notRegNoPremium' => __('Register to unlock Premium Features'),
			'notRegNoAll' => __('Register Plugin to unlock all features'),
			'needsd' => __('Needs:'),
			'fixMissingAddons' => __('Fix not Installed Addons'),
			'fix' => __('Fix'),
			'notRegNoAddOns' => __('Register to unlock AddOns'),
			'notRegNoSupport' => __('Register to unlock Support'),
			'notRegNoLibrary' => __('Register to unlock Library'),
			'notRegNoUpdates' => __('Register to unlock Updates'),
			'notRegNoTemplates' => __('Register to unlock Templates'),
			'areyousureupdateplugin' => __('Do you want to start the Update process?'),
			'arereadytoimport' => __('are ready for import!'),
			'addtocustomornew' => __('Do you want to add them to the "custom" category or create a new category?'),
			'addtocustom' => __('Add To Custom'),
			'addto' => __('Add To'),
			'createnewcategory' => __('Create New Category'),
			'updatenow' => __('Update Now'),
			'securityupdate' => __('Install Critical Update'),
			'toplevels' => __('Higher Level'),
			'siblings' => __('Current Level'),
			'otherfolders' => __('Other Folders'),
			'parent' => __('Parent Level'),
			'from' => __('from'),
			'to' => __('to'),
			'actionneeded' => __('Action Needed'),
			'updatedoneexist' => __('Done'),
			'updateallnow' => __('Update All'),
			'updatelater' => __('Update Later'),
			'addonsupdatemain' => __('The following AddOns require an update:'),
			'addonsupdatetitle' => __('AddOns need attention'),
			'updatepluginfailed' => __('Updating Plugin Failed'),
			'updatingplugin' => __('Updating Plugin...'),
			'licenseissue' => __('License validation issue Occured. Please contact our Support.'),
			'leave' => __('Back to Overview'),
			'reLoading' => __('Page is reloading...'),
			'updateplugin' => __('Update Plugin'),
			'updatepluginsuccess' => __('Slider Revolution Plugin updated Successfully.'),
			'updatepluginfailure' => __('Slider Revolution Plugin updated Failure:'),
			'updatepluginsuccesssubtext' => __('Slider Revolution Plugin updated Successfully to'),
			'reloadpage' => __('Reload Page'),
			'loading' => __('Loading'),
			'globalcolors' => __('Global Colors'),
			'elements' => __('Elements'),
			'loadingthumbs' => __('Loading Thumbnails...'),
			'jquerytriggered' => __('jQuery Triggered'),
			'atriggered' => __('&lt;a&gt; Tag Link'),
			'randomslide' => __('Random Slide'),
			'firstslide' => __('First Slide'),
			'lastslide' => __('Last Slide'),
			'nextslide' => __('Next Slide'),
			'previousslide' => __('Previous Slide'),
			'somesourceisnotcorrect' => __('Some Settings in Slider <strong>Source may not complete</strong>.<br>Please Complete All Settings in Slider Sources.'),
			'somelayerslocked' => __('Some Layers are <strong>Locked</strong> and/or <strong>Invisible</strong>.<br>Change Status in Timeline.'),
			'editorisLoading' => __('Editor is Loading...'),
			'addingnewblankmodule' => __('Adding new Blank Module...'),
			'opening' => __('Opening'),
			'featuredimages' => __('Featured Images'),
			'images' => __('Images'),
			'none' => __('None'),
			'select' => __('Select'),
			'reset' => __('Reset'),
			'custom' => __('Custom'),
			'out' => __('OUT'),
			'in' => __('IN'),
			'sticky_navigation' => __('Navigation Options'),
			'sticky_slider' => __('Module General Options'),
			'sticky_slide' => __('Slide Options'),
			'sticky_layer' => __('Layer Options'),
			'imageCouldNotBeLoaded' => __('Set a Slide Background Image to use this feature'),
			'slideTransPresets' => __('Slide Transition Presets'),
			'exporthtml' => __('HTML'),
			'simproot' => __('Root'),
			'releaseToAddLayer' => __('Release to Add Layer'),
			'releaseToUpload' => __('Release to Upload file'),
			'moduleZipFile' => __('Module .zip'),
			'importing' => __('Processing Import of'),
			'importfailure' => __('An Error Occured while importing'),
			'successImportFile' => __('File Succesfully Imported'),
			'importReport' => __('Import Report'),
			'updateNow' => __('Update Now'),
			'multiplechildrensel' => __('Multiple Children Selected'),
			'activateToUpdate' => __('Activate To Update'),
			'activated' => __('Activated'),
			'notActivated' => __('Not Activated'),
			'embedingLine1' => __('Standard Module Embedding'),
			'embedingLine2' => __('For the <b>CMS Pages and Blocks</b> insert the Shortcode:'),
			'embedingLine2a' => __('Or Use the <b>Insert Widget</b> button and choose the <b>Slider Revolution Widget</b> and select the slider.'),
			'embedingLine3' => __('You can add the new <b>Slider Revolution Widget Instance</b> and configure it to be displayed on the specific part of the page.'),
			'embedingLine4' => __('Advanced Module Embedding'),
			'embedingLine5' => __('For the <b>XML Layout Update</b> use the following code:'),
			'embedingLine6' => __('To add the slider inside of the <b>template file</b> use this one:'),
			'embedingLine7' => __('To add the slider only to single Pages, use:'),
			'noLayersSelected' => __('Select a Layer'),
			'layeraction_group_link' => __('Link Actions'),
			'layeraction_group_slide' => __('Slide Actions'),
			'layeraction_group_layer' => __('Layer Actions'),
			'layeraction_group_media' => __('Media Actions'),
			'layeraction_group_fullscreen' => __('Fullscreen Actions'),
			'layeraction_group_advanced' => __('Advanced Actions'),
			'layeraction_menu' => __('Menu Link & Scroll'),
			'layeraction_link' => __('Simple Link'),
			'layeraction_callback' => __('Call Back'),
			'layeraction_modal' => __('Open Slider Modal'),
			'layeraction_getAccelerationPermission' => __('iOS Gyroscope Permission'),
			'layeraction_scroll_under' => __('Scroll below Slider'),
			'layeraction_scrollto' => __('Scroll To ID'),
			'layeraction_jumpto' => __('Jump to Slide'),
			'layeraction_next' => __('Next Slide'),
			'layeraction_prev' => __('Previous Slide'),
			'layeraction_next_frame' => __('Next Frame'),
			'layeraction_prev_frame' => __('Previous Frame'),
			'layeraction_pause' => __('Pause Slider'),
			'layeraction_resume' => __('Play Slide'),
			'layeraction_close_modal' => __('Close Slider Modal'),
			'layeraction_open_modal' => __('Open Slider Modal'),
			'layeraction_toggle_slider' => __('Toggle Slider'),
			'layeraction_start_in' => __('Go to 1st Frame '),
			'layeraction_start_out' => __('Go to Last Frame'),
			'layeraction_start_frame' => __('Go to Frame "N"'),
			'layeraction_toggle_layer' => __('Toggle 1st / Last Frame'),
			'layeraction_toggle_frames' => __('Toggle "N/M" Frames'),
			'layeraction_start_video' => __('Start Media'),
			'layeraction_stop_video' => __('Stop Media'),
			'layeraction_toggle_video' => __('Toggle Media'),
			'layeraction_mute_video' => __('Mute Media'),
			'layeraction_unmute_video' => __('Unmute Media'),
			'layeraction_toggle_mute_video' => __('Toggle Mute Media'),
			'layeraction_toggle_global_mute_video' => __('Toggle Mute All Media'),
			'layeraction_togglefullscreen' => __('Toggle Fullscreen'),
			'layeraction_gofullscreen' => __('Enter Fullscreen'),
			'layeraction_exitfullscreen' => __('Exit Fullscreen'),
			'layeraction_simulate_click' => __('Simulate Click'),
			'layeraction_toggle_class' => __('Toggle Class'),
			'layeraction_none' => __('Disabled'),
			'backgroundvideo' => __('Background Video'),
			'videoactiveslide' => __('Video in Active Slide'),
			'firstvideo' => __('Video in Active Slide'),
			'addaction' => __('Add Action to '),
			'ol_images' => __('Images'),
			'ol_layers' => __('Layer Objects'),
			'ol_objects' => __('Objects'),
			'ol_modules' => __('Own Modules'),
			'ol_fonticons' => __('Font Icons'),
			'ol_moduletemplates' => __('Module Templates'),
			'ol_videos' => __('Videos'),
			'ol_svgs' => __('SVG\'s'),
			'ol_favorite' => __('Favorites'),
			'installed' => __('Installed'),
			'notinstalled' => __('Not Installed'),
			'setupnotes' => __('Setup Notes'),
			'requirements' => __('Requirements'),
			'installedversion' => __('Installed Version'),
			'cantpulllinebreakoutside' => __('Use LineBreaks only in Columns'),
			'availableversion' => __('Available Version'),
			'installingtemplate' => __('Installing Template'),
			'search' => __('Search'),
			'publish' => __('Publish'),
			'unpublish' => __('Unpublish'),
			'slidepublished' => __('Slide Published'),
			'slideunpublished' => __('Slide Unpublished'),
			'layerpublished' => __('Layer Published'),
			'layerunpublished' => __('Layer Unpublished'),
			'folderBIG' => __('FOLDER'),
			'moduleBIG' => __('MODULE'),
			'objectBIG' => __('OBJECT'),
			'packageBIG' => __('PACKAGE'),
			'thumbnail' => __('Thumbnail'),
			'imageBIG' => __('IMAGE'),
			'videoBIG' => __('VIDEO'),
			'iconBIG' => __('ICON'),
			'svgBIG' => __('SVG'),
			'fontBIG' => __('FONT'),
			'redownloadTemplate' => __('Re-Download Online'),
			'createBlankPage' => __('Create Blank Page'),
			'changingscreensize' => __('Changing Screen Size'),
			'qs_headlines' => __('Headlines'),
			'qs_content' => __('Content'),
			'qs_buttons' => __('Buttons'),
			'qs_bgspace' => __('BG & Space'),
			'qs_shadow' => __('Shadow'),
			'qs_shadows' => __('Shadow'),
			'saveslide' => __('Saving Slide'),
			'loadconfig' => __('Loading Configuration'),
			'updateselects' => __('Updating Lists'),
			'textlayers' => __('Text Layers'),
			'globalLayers' => __('Global Layers'),
			'slidersettings' => __('Slider Settings'),
			'animatefrom' => __('Animate From'),
			'animateto' => __('Keyframe #'),
			'transformidle' => __('Transform Idle'),
			'enterstage' => __('Anim From'),
			'leavestage' => __('Anim To'),
			'onstage' => __('Anim To'),
			'keyframe' => __('Keyframe'),
			'notenoughspaceontimeline' => __('Not Enough space between Frames.'),
			'framesizecannotbeextended' => __('Frame Size can not be Extended. Not enough Space.'),
			'backupTemplateLoop' => __('Loop Template'),
			'backupTemplateLayerAnim' => __('Animation Template'),
			'choose_image' => __('Choose Image'),
			'choose_video' => __('Choose Video'),
			'slider_revolution_shortcode_creator' => __('Slider Revolution Shortcode Creator'),
			'shortcode_generator' => __('Shortcode Generator'),
			'please_add_at_least_one_layer' => __('Please add at least one Layer.'),
			'shortcode_parsing_successfull' => __('Shortcode parsing successfull. Items can be found in step 3'),
			'shortcode_could_not_be_correctly_parsed' => __('Shortcode could not be parsed.'),
			'addonrequired' => __('Addon Required'),
			'installpackage' => __('Installing Template Package'),
			'doinstallpackage' => __('Install Template Package'),
			'installtemplate' => __('Install Template'),
			'checkversion' => __('Update To Latest Version'),
			'installpackageandaddons' => __('Install Template Package & Addon(s)'),
			'installtemplateandaddons' => __('Install Template & Addon(s)'),
			'licencerequired' => __('Activate License'),
			'searcforicon' => __('Search Icons...'),
			'savecurrenttemplate' => __('Current Settings (Click to Save as Preset)'),
			'customtransitionpresets' => __('Custom Presets'),
			'customtemplates' => __('Custom'),
			'overwritetemplate' => __('Overwrite Template ?'),
			'deletetemplate' => __('Delete Template ?'),
			'credits' => __('Credits'),
			'randomanimation' => __('Random Animation'),
			'transition' => __('Transition'),
			'duration' => __('Duration'),
			'enabled' => __('Enabled'),
			'global' => __('Global'),
			'install_and_activate' => __('Install Add-On'),
			'install' => __('Install'),
			'enableaddon' => __('Enable Add-On'),
			'disableaddon' => __('Disable Add-On'),
			'enableglobaladdon' => __('Enable Global Add-On'),
			'disableglobaladdon' => __('Disable Global Add-On'),
			'sliderrevversion' => __('Slider Revolution Version'),
			'checkforrequirements' => __('Check Requirements'),
			'activateglobaladdon' => __('Activate Global Add-On'),
			'activateaddon' => __('Activate Add-On'),
			'activatingaddon' => __('Activating Add-On'),
			'enablingaddon' => __('Enabling Add-On'),
			'addon' => __('Add-On'),
			'installingaddon' => __('Installing Add-On'),
			'disablingaddon' => __('Disabling Add-On'),
			'buildingSelects' => __('Building Select Boxes'),
			'warning' => __('Warning'),
			'blank_page_added' => __('Blank Page Created'),
			'blank_page_created' => __('Blank page has been created:'),
			'visit_page' => __('Visit Page'),
			'edit_page' => __('Edit Page'),
			'closeandstay' => __('Close'),
			'changesneedreload' => __('The changes you made require a page reload!'),
			'saveprojectornot ' => __('Save your project & reload the page or cancel'),
			'saveandreload' => __('Save & Reload'),
			'canceldontreload' => __('Cancel & Reload Later'),
			'saveconfig' => __('Save Configuration'),
			'updatingaddon' => __('Updating'),
			'addonOnlyInSlider' => __('Enable/Disable Add-On on Module'),
			'openQuickEditor' => __('Open Quick Content Editor'),
			'openQuickStyleEditor' => __('Open Quick Style Editor'),
			'sortbycreation' => __('Sort by Creation'),
			'creationascending' => __('Creation Ascending'),
			'sortbytitle' => __('Sort by Title'),
			'titledescending' => __('Title Descending'),
			'updatefromserver' => __('Update List'),
			'audiolibraryloading' => __('Audio Wave Library is Loading...'),
			'editModule' => __('Edit Module'),
			'editSlide' => __('Edit Slide'),
			'showSlides' => __('Show Slides'),
			'openInEditor' => __('Open in Editor'),
			'openFolder' => __('Open Folder'),
			'moveToFolder' => __('Move to Folder'),
			'loadingRevMirror' => __('Loading RevMirror Library...'),
			'lockunlocklayer' => __('Lock / Unlock Selected'),
			'nrlayersimporting' => __('Layers Importing'),
			'nothingselected' => __('Nothing Selected'),
			'layerwithaction' => __('Layer with Action'),
			'imageisloading' => __('Image is Loading...'),
			'importinglayers' => __('Importing Layers...'),
			'triggeredby' => __('Triggered By'),
			'import' => __('Imported'),
			'layersBIG' => __('LAYERS'),
			'intinheriting' => __('Responsivity'),
			'changesdone_exit' => __('The changes you made will be lost!'),
			'exitwihoutchangesornot' => __('Are you sure you want to continue?'),
			'areyousuretoexport' => __('Are you sure you want to export '),
			'areyousuretodelete' => __('Are you sure you want to delete '),
			'deletecustomcategory' => __('Delete Custom Category '),
			'deletecustomitem' => __('Delete Custom Item '),
			'areyousuretodeleteeverything' => __('Delete All Sliders and Folders included in '),
			'leavewithoutsave' => __('Leave without Save'),
			'updatingtakes' => __('Updating the Plugin may take a few moments.'),
			'exportslidertxt' => __('Downloading the Zip File may take a few moments.'),
			'exportslider' => __('Export Slider'),
			'yesexport' => __('Yes, Export Slider'),
			'yesdelete' => __('Yes, Delete Slider'),
			'yesdeleteit' => __('Yes, Delete'),
			'yesdeleteslide' => __('Yes, Delete Slide'),
			'yesdeleteall' => __('Yes, Delete All Slider(s)'),
			'stayineditor' => __('Stay in Edior'),
			'redirectingtooverview' => __('Redirecting to Overview Page'),
			'leavingpage' => __('Leaving current Page'),
			'ashtmlexport' => __('as HTML Document'),
			'preparingNextSlide' => __('Preparing Slide...'),
			'updatingfields' => __('Preparing Fields...'),
			'preparingdatas' => __('Preparing Data...'),
			'loadingcontent' => __('Loading Content...'),
			'copy' => __('Copy'),
			'paste' => __('Paste'),
			'thiswilldeletecustomitem' => __('This will delete the selected item. Items already embedded in modules will remain there.'),
			'thiswilldeletecustomcategory' => __('This will delete the Category and move the elements in the default "All" category.'),
			'framewait' => __('WAIT'),
			'frstframe' => __('1st Frame'),
			'lastframe' => __('Last Frame'),
			'onlyonaction' => __('on Action'),
			'cannotbeundone' => __('This action can not be undone !!'),
			'deleteslider' => __('Delete Slider'),
			'deleteslide' => __('Delete Slide'),
			'deletingslide' => __('This can be Undone only within the Current session.'),
			'deleteselectedslide' => __('Are you sure you want to delete the selected Slide:'),
			'cancel' => __('Cancel'),
			'addons' => __('Add-Ons'),
			'deletingsingleslide' => __('Deleting Slide'),
			'lastslidenodelete' => __('"Last Slide in Module. Can not be deleted"'),
			'deletingslider' => __('Deleting Slider'),
			'active_sr_tmp_obl' => __('Template & Object Library'),
			'active_sr_inst_upd' => __('Instant Updates'),
			'active_sr_one_on_one' => __('1on1 Support'),
			'noticepositionreseted' => __('Layer positions has been reset'),
			'parallaxsettoenabled' => __('Parallax is now generally Enabled'),
			'filtertransitionissuepre' => __('Some slide transitions do not support filters. If problems occur, please try a different slide transition / filter pairing'),
			'CORSERROR' => __('External Media can not be used  for WEBGL Transitions due CORS Policy issues'),
			'CORSWARNING' => __('Slider Revolution has successfully re-requested image to rectify above CORS error.'),
			'timelinescrollsettoenabled' => __('Scroll Based Timeline is now generally Enabled'),
			'feffectscrollsettoenabled' => __('Filter Effect Scroll is now generally Enabled'),
			'nolayersinslide' => __('Slide has no Layers'),
			'leaving' => __('Changes that you made may not be saved.'),
			'sliderasmodal' => __('Add Slider as Modal'),
			'register_to_unlock' => __('Register to unlock all Premium Features'),
			'premium_features_unlocked' => __('All Premium Features unlocked'),
			'premium_template' => __('PREMIUM TEMPLATE'),
			'rs_premium_content' => __('This is a Premium template from the Slider Revolution <a target="_blank" rel="noopener" href="https://www.sliderrevolution.com/examples/">template library</a>. It can only be used on this website with a <a target="_blank" rel="noopener" href="https://www.sliderrevolution.com/manual/quick-setup-register-your-plugin/?utm_source=admin&utm_medium=button&utm_campaign=srusers&utm_content=registermanual">registered license key</a>.'),
			'premium' => __('Premium'),
			'premiumunlock' => __('REGISTER LICENSE TO UNLOCK'),
			'tryagainlater' => __('Please try again later'),
			'quickcontenteditor' => __('Quick Content Editor'),
			'module' => __('Module'),
			'quickstyleeditor' => __('Quick Style Editor'),
			'all' => __('All'),
			'active_sr_to_access' => __('Register Slider Revolution<br>to Unlock Premium Features'),
			'membersarea' => __('Members Area'),
			'onelicensekey' => __('1 License Key per Website!'),
			'onepurchasekey' => __('1 Purchase Code per Website!'),
			'onelicensekey_info' => __('If you want to use your license key on another domain, please<br> deregister it in the members area or use a different key.'),
			'onepurchasekey_info' => __('If you want to use your purchase code on<br>another domain, please deregister it first or'),
			'registeredlicensekey' => __('Registered License Key'),
			'registeredpurchasecode' => __('Registered Purchase Code'),
			'registerlicensekey' => __('Register License Key'),
			'registerpurchasecode' => __('Register Purchase Code'),
			'registerCode' => __('Register this Code'),
			'registerKey' => __('Register this License Key'),
			'deregisterCode' => __('Deregister this Code'),
			'deregisterKey' => __('Deregister this License Key'),
			'active_sr_plg_activ' => __('Register Purchase Code'),
			'active_sr_plg_activ_key' => __('Register License Key'),
			'getpurchasecode' => __('Get a Purchase Code'),
			'getlicensekey' => __('Get a License Key'),
			'ihavepurchasecode' => __('I have a Purchase Code'),
			'ihavelicensekey' => __('I have a License Key'),
			'enterlicensekey' => __('Enter License Key'),
			'enterpurchasecode' => __('Enter Purchase Code'),
			'colrskinhas' => __('This Skin use'),
			'deleteskin' => __('Delete Skin'),
			'references' => __('References'),
			'colorwillkept' => __('The References will keep their colors after deleting Skin.'),
			'areyousuredeleteskin' => __('Are you sure to delete Color Skin?'),
			'svgcustomimport' => __('Custom File Import'),
			'importsvgfiles' => __('Import SVG Files'),
			'customsvgfile' => __('Custom SVG File'),
			'savecustomfile' => __('Import File'),
			'customfile' => __('Custom  File'),
			'uploadfirstitem' => __('Upload Your 1st Item'),
			'sltr_full' => __('Full'),
			'sltr_basic' => __('Base'),
			'sltr_fade' => __('Fade'),
			'sltr_fades' => __('Fade'),
			'sltr_slideinout' => __('Slide In, Slide Out'),
			'sltr_slideinoutfadein' => __('Slide & Fade In, Slide Out'),
			'sltr_slideinoutfadeinout' => __('Slide & Fade In, Slide & Fade Out'),
			'sltr_dddeffects' => __('3D Effects'),
			'sltr_slide' => __('Slide'),
			'sltr_slideover' => __('Simple Slide'),
			'sltr_remove' => __('Masked Slide Out'),
			'sltr_slidefadeinslideout' => __('Slide & Fade In, Slide Out'),
			'sltr_slidefadeinout' => __('Slide & Fade In Slide & Fade Out'),
			'sltr_parallax' => __('Parallax Slide'),
			'sltr_zoom' => __('Zoom'),
			'sltr_zoomslidein' => __('Slide In, Zoom Out'),
			'sltr_zoomslideout' => __('Zoom In, Slide Out'),
			'sltr_special' => __('Special'),
			'sltr_double' => __('Double Effect'),
			'sltr_filter' => __('Filter'),
			'sltr_effects' => __('Effects'),
			'sltr_cuts' => __('Paper Cuts'),
			'sltr_columns' => __('Columns'),
			'sltr_curtain' => __('Curtain'),
			'sltr_rotation' => __('Rotation'),
			'sltr_rows' => __('Rows'),
			'sltr_circle' => __('Circle'),
			'sltr_boxes' => __('Boxes'),
			'sltr_random' => __('Random'),
			'dov_1' => __('Dotted Small'),
			'dov_2' => __('Dotted Medium'),
			'dov_3' => __('Dotted Large'),
			'dov_4' => __('Horizontal Small'),
			'dov_5' => __('Horizontal Medium'),
			'dov_6' => __('Horizontal Large'),
			'dov_7' => __('Vertical Small'),
			'dov_8' => __('Vertical Medium'),
			'dov_9' => __('Vertical Large'),
			'dov_10' => __('Circles Small'),
			'dov_11' => __('Circles Medium'),
			'dov_12' => __('Diagonal 1'),
			'dov_13' => __('Diagonal 2'),
			'dov_14' => __('Diagonal 3'),
			'dov_15' => __('Diagonal 4'),
			'dov_16' => __('Cross')


		);

		return FA::apply_filters('revslider_get_javascript_multilanguage', $lang);
	}


	/**
	 * returns all image sizes that have the same aspect ratio, rounded on the second
	 * @since: 6.1.4
	 **/
	public function get_same_aspect_ratio_images($images){
		$return = array();
		$images = (array)$images;

		if(!empty($images)){
			$objlib = new RevSliderObjectLibrary();
			$upload_dir = FA::wp_upload_dir();

			foreach($images as $key => $image){
				//check if we are from object library
				if($objlib->_is_object($image)){
					$_img = $image;
					$image = $objlib->get_correct_size_url($image, 100, true);
					$objlib->_check_object_exist($image); //check to redownload if not downloaded yet

					$sizes = $objlib->get_sizes();
					$return[$key] = array();

					if(!empty($sizes)){
						foreach($sizes as $size){
							$url = $objlib->get_correct_size_url($image, $size);
							$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
							$_size = getimagesize($file);
							$return[$key][$size] = array(
								'url'	=> $url,
								'width'	=> $this->get_val($_size, 0),
								'height'=> $this->get_val($_size, 1),
								'size'	=> filesize($file)
							);

							if($_img === $url) $return[$key][$size]['default'] = true;
						}

						//$image = $objlib->get_correct_size_url($image, 100, true);
						$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image);
						$_size = getimagesize($file);
						$return[$key][100] = array(
							'url'	=> $image,
							'width'	=> $this->get_val($_size, 0),
							'height'=> $this->get_val($_size, 1),
							'size'	=> filesize($file)
						);
						if($_img === $return[$key][100]['url']) $return[$key][100]['default'] = true;
					}
				}else{
					$_img = (intval($image) === 0) ? $this->get_image_id_by_url($image) : $image;
					$img_data = FA::wp_get_attachment_metadata($_img);

					if(!empty($img_data)){
						$return[$key] = array();
						$ratio = round($this->get_val($img_data, 'width', 1) / $this->get_val($img_data, 'height', 1), 2);
						$sizes = $this->get_val($img_data, 'sizes', array());
						$file = $upload_dir['basedir'] .'/'. $this->get_val($img_data, 'file');
						$return[$key]['full'] = array(
							'url'	=> $upload_dir['baseurl'] .'/'. $this->get_val($img_data, 'file'),
							'width'	=> $this->get_val($img_data, 'width'),
							'height'=> $this->get_val($img_data, 'height'),
							'size'	=> filesize($file)
						);
						if (FA::getImagesHelper()->image_to_url($image) === $return[$key]['full']['url']) $return[$key]['full']['default'] = true;

						if(!empty($sizes)){
							foreach($sizes as $sn => $sv){
								$_ratio = round($this->get_val($sv, 'width', 1) / $this->get_val($sv, 'height', 1), 2);
								if($_ratio === $ratio){
									$i = FA::wp_get_attachment_image_src($_img, $sn);
									if($i === false) continue;

									$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $this->get_val($i, 0));
									$return[$key][$sn] = array(
										'url'	=> $this->get_val($i, 0),
										'width'	=> $this->get_val($sv, 'width'),
										'height'=> $this->get_val($sv, 'height'),
										'size'	=> filesize($file)
									);
									if($image === $return[$key][$sn]['url']) $return[$key][$sn]['default'] = true;
								}
							}
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * returns all files plus sizes of JavaScript and css files used by the AddOns
	 * @since. 6.1.4
	 **/
	public function get_addon_sizes($addons){
		$sizes = array();

		if(empty($addons) || !is_array($addons)) return $sizes;

		$_css = '/public/assets/css/';
		$_js = '/public/assets/js/';
		//these are the sizes before the AddOns where updated
		$_a = array(
			'revslider-404-addon' => array(),
			'revslider-backup-addon' => array(),
			'revslider-beforeafter-addon' => array(
				$_css .'revolution.addon.beforeafter.css' => 3512,
				$_js .'revolution.addon.beforeafter.min.js' => 21144
			),
			'revslider-bubblemorph-addon' => array(
				$_css .'revolution.addon.bubblemorph.css' => 341,
				$_js .'revolution.addon.bubblemorph.min.js' => 11377
			),
			'revslider-domain-switch-addon' => array(),
			'revslider-duotonefilters-addon' => array(
				$_css .'revolution.addon.duotone.css' => 11298,
				$_js .'revolution.addon.duotone.min.js' => 1232
			),
			'revslider-explodinglayers-addon' => array(
				$_css .'revolution.addon.explodinglayers.css' => 704,
				$_js .'revolution.addon.explodinglayers.min.js' => 19012
			),
			'revslider-featured-addon' => array(),
			'revslider-filmstrip-addon' => array(
				$_css .'revolution.addon.filmstrip.css' => 843,
				$_js .'revolution.addon.filmstrip.min.js' => 5409
			),
			'revslider-gallery-addon' => array(),
			'revslider-liquideffect-addon' => array(
				$_css .'revolution.addon.liquideffect.css' => 606,
				$_js .'pixi.min.js' => 514062,
				$_js .'revolution.addon.liquideffect.min.js' => 11899
			),
			'revslider-login-addon' => array(),
			'revslider-maintenance-addon' => array(),
			'revslider-paintbrush-addon' => array(
				$_css .'revolution.addon.paintbrush.css' => 676,
				$_js .'revolution.addon.paintbrush.min.js' => 6841
			),
			'revslider-panorama-addon' => array(
				$_css .'revolution.addon.panorama.css' => 1823,
				$_js .'three.min.js' => 504432,
				$_js .'revolution.addon.panorama.min.js' => 12909
			),
			'revslider-particles-addon' => array(
				$_css .'revolution.addon.particles.css' => 668,
				$_js .'revolution.addon.particles.min.js' => 33963
			),
			'revslider-polyfold-addon' => array(
				$_css .'revolution.addon.polyfold.css' => 900,
				$_js .'revolution.addon.polyfold.min.js' => 5125
			),
			'revslider-prevnext-posts-addon' => array(),
			'revslider-refresh-addon' => array(
				$_js .'revolution.addon.refresh.min.js' => 920
			),
			'revslider-rel-posts-addon' => array(),
			'revslider-revealer-addon' => array(
				$_css .'revolution.addon.revealer.css' => 792,
				$_css .'revolution.addon.revealer.preloaders.css' => 14792,
				$_js .'revolution.addon.revealer.min.js' => 7533
			),
			'revslider-sharing-addon' => array(
				$_js .'revslider-sharing-addon-public.js' => 6232
			),
			'revslider-slicey-addon' => array(
				$_js .'revolution.addon.slicey.min.js' => 4772
			),
			'revslider-snow-addon' => array(
				$_js .'revolution.addon.snow.min.js' => 4823
			),
			'revslider-template-addon' => array(),
			'revslider-typewriter-addon' => array(
				$_css .'typewriter.css' => 233,
				$_js .'revolution.addon.typewriter.min.js' => 8038
			),
			'revslider-weather-addon' => array(
				$_css .'revslider-weather-addon-icon.css' => 3699,
				$_css .'revslider-weather-addon-public.css' => 483,
				$_css .'weather-icons.css' => 31082,
				$_js .'revslider-weather-addon-public.js' => 5335
			),
			'revslider-whiteboard-addon' => array(
				$_js .'revolution.addon.whiteboard.min.js' => 10649
			)
		);

		//AddOns can apply/modify the default data here
		$_a = FA::apply_filters('revslider_create_slider_page', $_a, $_css, $_js, $this);

		foreach($addons as $addon){
			if(!isset($_a[$addon])) continue;
			$sizes[$addon] = 0;
			if(!empty($_a[$addon])){
				foreach($_a[$addon] as $size){
					$sizes[$addon] += $size;
				}
			}
			//$sizes[$addon] = $_a[$addon];
		}

		return $sizes;
	}

	/**
	 * returns a list of found compressions
	 * @since. 6.1.4
	 **/
	public function compression_settings(){
		$match	= array();
		$com	= array('gzip', 'compress', 'deflate', 'br'); //'identity' -> means no compression prefered
		$enc	= $this->get_val($_SERVER, 'HTTP_ACCEPT_ENCODING');

		if(empty($enc)) return $match;

		foreach($com as $c){
			if(strpos($enc, $c) !== false) $match[] = $c;
		}

		return $match;
	}

}
