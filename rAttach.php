<?php
/*
Plugin Name: rAttach
Plugin URI: http://www.nutt.net/tag/rattach/
Description:  Adds a list of all attached files to the end of posts and pages with
              links to download.
Version: 0.1.1
Author: Ryan Nutt
Author URI: http://www.nutt.net
*/

/*  Copyright 2011 Ryan Nutt

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Thanks to http://www.famfamfam.com/lab/icons/silk/ for the icons :)
 */

/* If you'd like to add icons for other extensions, enter them as part of
 * this array, in lower case.  Any extension not found in this list will
 * get a generic white page.
 */
$rAttachFileTypes = array(
  'c'     => 'page_white_code.png',
  'doc'   => 'page_white_word.png',
  'docx'  => 'page_white_word.png',
  'gif'   => 'page_white_camera.png',
  'java'  => 'page_white_code.png',
  'jpeg'  => 'page_white_camera.png',
  'jpg'   => 'page_white_camera.png',
  'js'    => 'page_white_code.png',
  'pdf'   => 'page_white_acrobat.png',
  'php'   => 'page_white_code.png',
  'png'   => 'page_white_camera.png',
  'sb'    => 'page_white_code.png',
  'xls'   => 'page_white_excel.png',
  'xlsx'  => 'page_white_excel.png',
  'zip'   => 'page_white_compressed.png',

);

/* Add WordPress filter and styles  */
add_filter('the_content', 'rAttachList');
add_action('admin_init', 'rAttachInit');
register_activation_hook(__FILE__,'rAttachActivate');
register_deactivation_hook(__FILE__,'rAttachDeactivate');

/** Add settings */
function rAttachInit() {
    add_settings_section('rAttach-settings', 'rAttach Settings', 'rAttachMenu', 'writing');
    add_settings_field('rAttach-default', 'Display by Default', 'rAttachForm', 'writing', 'rAttach-settings');
    register_setting('writing', 'rAttach-default');
    
}

/** Create the options field when activated  */
function rAttachActivate() {
    add_option('rAttach-default', 1);
}

/** Remove the options field when deactivated */
function rAttachDeactivate() {
    delete_option('rAttach-default'); 
}

/** Display the checkbox    */
function rAttachForm() {
    $opt = (bool)get_option('rAttach-default', 1); ?>
    <input type="checkbox" name="rAttach-default" id="rAttach-default"<?php checked($opt==true); ?> />
<?php
}

/**
 * Output the table of attached files when appropriate.
 */
function rAttachList($input = '') {
    
  global $post, $rAttachFileTypes;

  $showDefault = (bool)get_option('rAttach-default', 1);
  $forceThis = (bool)get_post_meta($post->ID, 'rAttach-on', true);
  $forceHide = (bool)get_post_meta($post->ID, 'rAttach-off', true);

  if (! ($showDefault || $forceThis) || $forceHide) {
      return $input; 
  }

  $myChildren = get_children(array(
    'numberposts' => -1,
    'post_parent' => $post->ID,
    'post_type' => 'attachment',
    'orderby' => 'title',
    'order' => 'ASC'
  ));
  
  if ($myChildren) {
    
    $out = '<div class="rAttachFiles">';
    $out .= '<h4 class="rAttachFilesHeader">Attached Files</h4>';
    $out .= '<table class="rAttachTable">';
    foreach ($myChildren as $child) {

      /* Get the icon file  */
      $fileInfo = pathinfo($child->guid);
      $fileInfo['extension'] = strtolower($fileInfo['extension']);
      if (!empty($rAttachFileTypes[$fileInfo['extension']])) {
        $fileIcon = $rAttachFileTypes[$fileInfo['extension']];
      }
      else {
        $fileIcon = 'page_white.png';
      }

      $out .= '<tr class="rAttachRow">';
      $out .= '<td class="rAttachIconCell"><img src="'.plugins_url('images/'.$fileIcon, __FILE__).'"></td>';

      $out .= '<td class="rAttachFilenameCell"><a href="'.$child->guid.'" title="Download file">'.$fileInfo['basename'].'</a></td>';

      $filePath = str_replace(get_bloginfo('siteurl').'/', ABSPATH, $child->guid);

      if (file_exists($filePath)) {
        $fileSize = number_format(filesize($filePath)/1024, 2).'k';
      }
      else {
        $fileSize = ''; 
      }

      $out .= '<td class="rAttachSize">'.$fileSize.'</td>';


      $out .= '</tr>';
    }
    $out .= '</table>';
    $out .= '</div>';

    $input .= $out; 
  }

  return $input;
}

/** Output instructional text displayed on the options page */
function rAttachMenu() {
    ?>
Determines whether rAttach includes the list of attached files by default.  If you
set default to on then adding a meta field to your post named rAttach-off with any
value other than empty or a zero will disable rAttach for that post.  rAttach-on works
the same way to turn on rAttach for specific posts when you have disabled it by
default.
<?php
}
?>