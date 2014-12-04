<?php

/*
Plugin Name: poweredbycoffee avatar
Plugin URI: http://poweredbycoffee.co.uk
Description: Overwrite get_avatar to feature a defult of getting a local avatar then falling back to gravatar.  Requires a field "avatar" in the user_meta table that is the ID of an attachent
Version: 0.1
Author: Stewart Ritchie
Author URI: http://poweredbycoffee.co.uk
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/


if ( !function_exists('get_avatar') ){
function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false ) {
  if ( ! get_option('show_avatars') )
    return false;

  if ( false === $alt)
    $safe_alt = '';
  else
    $safe_alt = esc_attr( $alt );

  if ( !is_numeric($size) )
    $size = '96';

  $email = '';
  if ( is_numeric($id_or_email) ) {
    $id = (int) $id_or_email;
    $user = get_userdata($id);
    if ( $user )
      $email = $user->user_email;
  } elseif ( is_object($id_or_email) ) {
    // No avatar for pingbacks or trackbacks

    /**
     * Filter the list of allowed comment types for retrieving avatars.
     *
     * @since 3.0.0
     *
     * @param array $types An array of content types. Default only contains 'comment'.
     */
    $allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
    if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
      return false;

    if ( ! empty( $id_or_email->user_id ) ) {
      $id = (int) $id_or_email->user_id;
      $user = get_userdata($id);
      if ( $user )
        $email = $user->user_email;
    }

    if ( ! $email && ! empty( $id_or_email->comment_author_email ) )
      $email = $id_or_email->comment_author_email;
  } else {
    $email = $id_or_email;
    $user = get_user_by("email", $email );
  }

  // look for the avatar metafield, otherwise default to gravatar
  if($user->__isset("avatar")){
  	  $avatar = wp_get_attachment_image_src($user->__get("avatar"), array($size, $size) ) ;
      $out = esc_url($avatar[0]);
      $avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";

  } else {


    if ( empty($default) ) {
      $avatar_default = get_option('avatar_default');
      if ( empty($avatar_default) )
        $default = 'mystery';
      else
        $default = $avatar_default;
    }

    if ( !empty($email) )
      $email_hash = md5( strtolower( trim( $email ) ) );

    if ( is_ssl() ) {
      $host = 'https://secure.gravatar.com';
    } else {
      if ( !empty($email) )
        $host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
      else
        $host = 'http://0.gravatar.com';
    }

    if ( 'mystery' == $default )
      $default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
    elseif ( 'blank' == $default )
      $default = $email ? 'blank' : includes_url( 'images/blank.gif' );
    elseif ( !empty($email) && 'gravatar_default' == $default )
      $default = '';
    elseif ( 'gravatar_default' == $default )
      $default = "$host/avatar/?s={$size}";
    elseif ( empty($email) )
      $default = "$host/avatar/?d=$default&amp;s={$size}";
    elseif ( strpos($default, 'http://') === 0 )
      $default = add_query_arg( 's', $size, $default );

    if ( !empty($email) ) {
      $out = "$host/avatar/";
      $out .= $email_hash;
      $out .= '?s='.$size;
      $out .= '&amp;d=' . urlencode( $default );

      $rating = get_option('avatar_rating');
      if ( !empty( $rating ) )
        $out .= "&amp;r={$rating}";

      $out = str_replace( '&#038;', '&amp;', esc_url( $out ) );
      $avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
    } else {
      $out = esc_url( $default );
      $avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
    }

  }

  /**
   * Filter the avatar to retrieve.
   *
   * @since 2.5.0
   *
   * @param string            $avatar      Image tag for the user's avatar.
   * @param int|object|string $id_or_email A user ID, email address, or comment object.
   * @param int               $size        Square avatar width and height in pixels to retrieve.
   * @param string            $alt         Alternative text to use in the avatar image tag.
   *                                       Default empty.
   */
  return apply_filters( 'get_avatar', $avatar, $id_or_email, $size, $default, $alt );
}

}
