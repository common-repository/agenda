<?php
/*
Plugin Name: Agenda
Description: Creates events posts for your wordpress.
Version: 1.7
Author: DGmike
Author URI: http://www.dgmike.com.br
*/

class Agenda {
  static $wpdb = false;
  static $info = array ();
  /**
   * Formats a file template.
   *
   * @param string $file
   * @param array $subs Array in format array ('VAR' => 'representant')
   * @return string
   */
  public function _formatTemplate ($file, array $subs=array(), $type='default') {
    $tplObj = new FileReader($file);
    $tpl = $tplObj->read($tplObj->length());
    if (count($subs)>0)
      foreach ($subs as $k => $v)
        $tpl=str_replace("{{$k}}",$v,$tpl);
    if ($type==='css') {
      $search  = array ('/(\{|;)\n\s*/i' , '/:\s+/' , '/\n+/');
      $replace = array ('${1}'           , ':'      , "\n"   );
      foreach ($search as $k=>$v)
        $tpl = preg_replace ($v, $replace[$k], $tpl);
    }
    return $tpl;
  }
  /**
   * Verify if the string is a valid date
   *
   * @param string $date in this format: YYYY-MM-DD HH:NN or YYYY-MM-DD
   * @return bool
   */
  public function _isValidDate($date) {
    if ('string' !== gettype($date)) return false;
    $date=trim($date); # Tratating
    $match = preg_match ('@^(\d{4}-\d{2}-\d{2})(\s\d{2}:\d{2})?$@', $date); # Verify the format of string
    if (!(bool) $match) return false;
    if (sizeof(split(' ',$date)) == 1) $time = '00:00';
    else list($date, $time) = split(' ', $date);

    list($year, $month, $day) = split('-', $date);
    list($hour, $minute) = split(':', $time);

    $microTime = mktime($hour, $minute, 0, $month, $day, $year);
    if (date('Y-m-d H:i', $microTime) !== "$date $time") return false;

    $time = compact ('year', 'month', 'day', 'hour', 'minute');
    return array ($microTime, $time, 'microtime' => $microTime, 'time' => $time);
  }
  /**
   * Gets the before printed, and generates a new printed content, returnig it
   *
   * @param $before Referenced where the printed content will be bufered
   * @param string $function String that runs, generating a new print
   * @param mixed $q Any value to pass
   * @return string
   */
  public function _buferizeAndGet(&$before, $function, $q='') {
    $return = '';
    $before = ob_get_contents();
    ob_end_clean();
    ob_start();
    eval($function); // Its dangerous, use carefuly...
    $return = ob_get_contents();
    ob_end_clean();
    ob_start();
    return $return;
  }
  /**
   * Adds or Update a unique meta in defined post
   *
   * @param int $id       Id of post
   * @param string $key   Key of meta
   * @param string $value Value of meta
   * @return void
   */
  public function _putMeta ($id, $key, $value) {
    add_post_meta($id, $key, $value, true) or update_post_meta($id, $key, $value);
  }
  /**
   * Return an array contain the days of the week
   *
   * @return array
   */
  public function _week () {
      return array ('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
  }
  /**
   * Initializate the plugin
   *
   * @return object $wpdb
   */
  public function init () {
    global $wpdb;
    Agenda::$wpdb = $wpdb;
    Agenda::$info = array('plugin_dir' => dirname(__FILE__));
    add_action ('admin_menu', array('Agenda','options'));
    register_sidebar_widget('Agenda', array('Agenda', 'widget_agenda_lista'));
    return Agenda::$wpdb;
  }
  /**
   * Install the plugin, create the categorie and put the id in the wordpress options
   *
   * @return true
   */
  public function install () {
    $wpdb = Agenda::init();
    $cat_id = get_option ('agenda_category');
    if (!$cat_id) {
      $category = $wpdb->get_var("SELECT * FROM $wpdb->terms WHERE slug = '_agenda'");
      if (!$category) {
        $cat = array('cat_name' => '_agenda', 'category_description' => 'Events Category', 'category_nicename' => '_agenda');
        $cat_id = wp_insert_category ($cat);
      }
      add_option ('agenda_category', $cat_id);
    }
    return true;
  }
  /**
   * Makes the menu options, calling the right function
   */
  public function options (){
    add_action('load-edit.php', array('Agenda', 'loadEditPage'));
    add_management_page (__('Events', 'agenda'), __('Events', 'agenda'), 8, 'agenda/manage', array('Agenda','manipulate_list'));
    add_action('load-write_page_agenda/write', array('Agenda', 'loadWriteEvent'));
    add_submenu_page('post.php', 'Write', 'Event', 8, 'agenda/write', array('Agenda', 'manipulate'));
    add_options_page(__('Agenda', 'agenda'), __('Agenda', 'agenda'), 10, 'agenda/options', array('Agenda', 'option_page'));
    register_widget_control('Agenda', array('Agenda', 'widget_agenda_lista_control'));
  }
  /**
   * Add filter for manage posts
   */
  public function loadEditPage() {
    add_filter('request', array ('Agenda', 'filter_posts'));
  }
  /**
   * Filtrer for your posts on manage posts
   * removing posts that mathcer the agenda category
   *
   * @param array $q the request
   * @return string
   */
  public function filter_posts ($q) {
    $q['cat'] = (strlen(trim($q['cat']))>0) ? $q['cat'].',' : '';
    $q['cat'] .= '-'.get_option('agenda_category');
    return $q;
  }
  /**
   * Creates a configuration for the widget
   */
  public function widget_agenda_lista_control() {
    $dir = Agenda::$info['plugin_dir'];
    include_once ("{$dir}/data.form.php");
    $options = get_option('widget_agenda_lista');
    if ( !is_array($options) )
      $options = array('title'=> __('List of events', 'agenda'),'next'=>5,'destak'=>true, 'show_date' => true, 'before'=>'<li>', 'after'=>'</li>');
    if ($_POST['widget_agenda_lista-submit']) {
      foreach (array('title', 'next', 'before', 'after', 'destak', 'show_date') as $item)
        $options[$item]=$_POST['widget_agenda_lista-'.$item];
      update_option('widget_agenda_lista', $options);
    }
    foreach (array('destak', 'show_date') as $item)
      $options[$item] = $options[$item] == 'true' ? ' checked="checked"' : '';
    extract($options);
    print Agenda::_formatTemplate("$dir/widget.html", widgetControl() + array(
      'v_title'     => $title,
      'v_next'      => $next,
      'v_destak'    => $destak,
      'v_show_date' => $show_date,
      'v_before'    => $before,
      'v_after'     => $after,
    ));
  }
  /**
   * Generates a widget for your events
   */
  public function widget_agenda_lista ($args) {
    extract($args);
    $options=get_option('widget_agenda_lista');
    foreach (array('destak', 'show_date') as $item)
      $options[$item] = ($options[$item] === 'true');
    extract($options);
    echo $before_widget . $before_title . $title . $after_title;
    agenda_list (compact('next', 'destak', 'show_date', 'before', 'after'));
    echo $after_widget;
  }
  /**
   * Load Scripts on Edit/Write Event
   */
  public function loadWriteEvent () {
    wp_enqueue_script('post');
    if ( user_can_richedit() ) wp_enqueue_script('editor');
    add_thickbox();
    wp_enqueue_script('thickbox');
    wp_enqueue_script('media-upload');
  }
  /**
   * Make option Page
   */
  public function option_page () {
    $dir=Agenda::$info['plugin_dir'];
    $wp_nonce_field = Agenda::_buferizeAndGet(&$before, 'wp_nonce_field("update-options")');
    $options = array ();
    $categories=get_categories(array('hide_empty' => false));
    foreach ($categories as $cat)
      $options[] = sprintf('<option value="%s"%s>%s</option>',
                            $cat->cat_ID,
                            ($cat->cat_ID == get_option('agenda_category') ? ' selected="selected"' : ''),
                            $cat->category_nicename
                          );
    $options = implode ("\n", $options);
    print $before . Agenda::_formatTemplate("$dir/form_option.html", array(
      'TITLE'           => __('Events Options', 'agenda'),
      'UPDATE_OPTIONS'  => $wp_nonce_field,
      'OPTIONS'         => $options,
      'CATEGORY'        => __('Global Category<br /><small>Who manipulate yours events?</small>', 'agenda'),
      'SAVE'            => __('Save', 'agenda')
    ));
  }
  /**
   * Generates a form to make an event
   *
   * @param bool $print If true, prints this form
   */
  public function form ($post = array(), $msg = '', $print = true) {
    $wpdb = Agenda::init();
    $dir = Agenda::$info['plugin_dir'];
    $style  = Agenda::_formatTemplate("$dir/style.css", array(), 'css');
    $script = Agenda::_formatTemplate("{$dir}/script.js");
    $data = Agenda::formatForForm(&$post, &$msg);
    $return = Agenda::_formatTemplate("$dir/form.html", $data + array('STYLE' => $style, 'SCRIPT' => $script));
    if ($print) print $return;
    return $return;
  }
  /**
   * Formats a post to use in form
   *
   * @param $post The $_POST/$_GET/$_REQUEST
   * @param $msg  Message to use in your form
   */
  public function formatForForm($post = array(), $msg = '') {
    global $current_user, $user_ID;
    $wpdb = Agenda::init();
    $dir = Agenda::$info['plugin_dir'];
    include_once ("{$dir}/data.form.php");

    if (trim($msg))
      $msg = '<div id="message" class="updated fade"><p>'.$msg.'</p></div>';

    $default_post = AgendaEmptyValuesForm() + array('v_author' => $user_ID);
    $post = array_merge((array) $default_post, (array) $post, $_REQUEST);
    $post['v_author'] = wp_dropdown_users( array('include' => $authors, 'name' => 'v_author', 'selected' => $post['v_author'], 'echo' => false) );

    foreach (array('v_pings' => 'open', 'v_destak' => '1', 'v_comments' => 'open') as $key => $value) {
      $post[$key] = Agenda::_buferizeAndGet(&$before, 'checked($q["'.$key.'"], "'.$value.'");', $post);
      print $before; // For correct the bug...
    }
    $post['v_all_day'] = $post['v_all_day'] ? ' checked="checked"' : '';
    $editor = Agenda::_buferizeAndGet(&$before, "the_editor(\$q['content'], 'content', 'title', true);", $post); print $before;

    $post['v_repeat_monthly_by_day_of_the_'.($post['repeat_monthly_by'] == 'week' ? 'week' : 'month')] = ' checked="checked"';
    $post['v_range_'.($post['range'] == 'until' ? 'until' : 'never')] = ' checked="checked"';

    if (in_array($post['repeat'], array('no_repeat','daily','weekly','monthly','yearly')))
      $post['v_repeat_'.$post['repeat']] = ' checked="checked"';

    foreach (array ('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') as $item)
      if ($post['v_repeat_weekly_on_'.$item])
        $post['v_repeat_weekly_on_'.$item] = ' checked="checked"';

    $cats = get_categories ('hide_empty=0&child_of='.get_option('agenda_category'));

    $options_calendar = array ();
    foreach ($cats as $cat)
      $options_calendar[] = sprintf ('<option value="%s"%s>%s</option>',
                                      $cat->term_id,
                                      in_array($cat->term_id, $post['v_calendar']) ? ' selected="selected"' : '',
                                      $cat->name
                                    );

    $options = array();
    foreach (array ('repeat_weekly_every', 'repeat_daily_every', 'repeat_monthly_every', 'repeat_yearly_every') as $v)
      foreach (range(1,30) as $i)
        $options[$v][] = vsprintf(
          '<option value="%1$s"%2$s>%1$s</option>',
          array ($i, $post[$v] == $i ? ' selected="selected"' : '')
        );
    array_walk($options, create_function('&$a', '$a = implode ("", $a);'));

    $dados = AgendaDataForm() + array (
      'MSG'               => $msg,
      'INPUT_ID'          => sprintf ('<input type="hidden" name="event_id" value="%s"  />', $post['event_id'] ? $post['event_id'] : '0'),
      'DAILY_OPTIONS'     => $options['repeat_daily_every'],
      'WEEKLY_OPTIONS'    => $options['repeat_weekly_every'],
      'MONTHLY_OPTIONS'   => $options['repeat_monthly_every'],
      'YEARLY_OPTIONS'    => $options['repeat_yearly_every'],
      'OPTIONS_CALENDAR'  => implode ("\n", $options_calendar),
      'THE_EDITOR'        => $editor,
    );
    return $dados + $post;
  }
  /**
   * Manipulate, create (save) and edit form
   *
   * @param array $request
   * @param bool $print
   * @return string
   */
  public function manipulate($request = array(), $print = true) {
    global $event_errors;
    if ($_REQUEST) $request = (array) $request + (array) $_REQUEST;
    $mensagem = '';
    if ($request['all_day'] == 'all_day')
      $request['v_all_day'] = ' checked="checked"';
    if ($request['submit'] || $request['submit-publish']) {
      $request['event_id'] = Agenda::saver();
      if ($request['event_id'])
        $mensagem = __('The event has been saved. Go to <a href="edit.php?page=agenda/manage">manage events</a>.', 'agenda');
      else
        $mensagem = __('Houston, we have a problem. Your event can\'t be saved.<br />' . implode('<br />', $event_errors));
    }
    if ($request['edit_id']) {
      $request = Agenda::req($request['edit_id']);
    }
    return Agenda::form($request, $mensagem, $print);
  }
  /**
   * Generates a list of events for manipulate
   *
   */
  public function manipulate_list () {
    $p = preg_match('@^\d+$@', $_REQUEST['p']) ? $_REQUEST['p'] : 1;
    $message = '';
    if ($_GET['delete_id'])
      $message = vsprintf('<br class="clear" /><div id="message" class="updated fade"><p>%1$s %2$s</p></div>', array(
        __('Are you sure that you want to delete this event(s)?', 'agenda'),
        sprintf ('<a href="edit.php?page=agenda/manage&delete_id[]=%s&confirm=1" class="button">%s</a>',
                  implode ('&delete_id[]=',$_GET['delete_id']),
                  __('Yes', 'agenda')
                )
      ));
    if ($_GET['delete_id'] && $_GET['confirm']) {
      foreach ($_GET['delete_id'] as $item)
        wp_delete_post((int) $item);
      $message = vsprintf('<br class="clear" /><div id="message" class="updated fade"><p>%1$s</p></div>', array(
        __('The event(s) has been deleted sussefuly.', 'agenda'),
      ));
    }

    $dir = Agenda::$info['plugin_dir'];
    $args = array (
      'post_status' => $_GET['status'] ? $_GET['status'] : 'blank',
      'numberposts' => 10,
      'offset'      => 10*($p-1),
      'category'    => get_option('agenda_category'),
    );
    $page_links = paginate_links( array(
      'base' => add_query_arg( 'p', '%#%' ),
      'format' => '',
      'total' => ceil(count(get_posts(array('numberposts'=>-1,'category'=>get_option('agenda_category'))))/10),
      'current' => $p,
    ));
    if ( $page_links ) $page_links = "<div class='tablenav-pages'>$page_links</div>";

    $posts = get_posts($args);
    $rows = array();
    if (!isset($_GET['delete_id'])) $_GET['delete_id'] = array();
    foreach ($posts as $post) {
      $q = array_map(create_function('$a', 'return $a[0];'), get_post_custom($post->ID));
      switch ( $post->post_status ) {
        case 'publish' :
        case 'private' :
          $status = __('Published', 'agenda');break;
        case 'future' :
          $status = __('Scheduled', 'agenda');break;
        case 'pending' :
          $status = __('Pending Review', 'agenda');break;
        case 'draft' :
          $status = __('Unpublished', 'agenda');break;
      }
      $vars = array(
        'P'             => $p,
        'ID'            => $post->ID,
        'TITLE'         => $post->post_title,
        'DATE'          => date('Y-m-d H:i', $q['_start']),
        'CLASS_ACTIVE'  => in_array ($post->ID, $_GET['delete_id']) ? 'class="active"' : '',
        'STATUS'        => $status,
      );
      $rows[] = Agenda::_formatTemplate("{$dir}/manipulate_list_rows.html", $vars);
    }
    $vars = array(
      'ALL'               => __('All', 'agenda'),
      'PUBLISHED'         => __('Published', 'agenda'),
      'DRAFT'             => __('Draft', 'agenda'),
      'CURRENT_ALL'       => !$_GET['status'] ? 'class="current" ' : '',
      'CURRENT_PUBLISHED' => $_GET['status'] == 'publish' ? 'class="current" ' : '',
      'CURRENT_DRAFT'     => $_GET['status'] == 'draft' ? 'class="current" ' : '',
      'STATUS'            => __('Status'),
      'P'                 => $p,
      'NEW'               => __('add new', 'agenda'),
      'MANAGE_EVENTS'     => __('Manage Events', 'agenda'),
      'DATE'              => __('Date', 'agenda'),
      'TITLE'             => __('Title', 'agenda'),
      'ACTIONS'           => __('Actions', 'agenda'),
      'ROWS'              => implode ("\n\n", $rows),
      'MESSAGE'           => $message,
      'DELETE'            => __('Delete Selected Events', 'agenda'),
      'PAGE_LINKS'        => $page_links,
    );
    $return = Agenda::_formatTemplate("{$dir}/manipulate_list.html", $vars);
    print $return;
  }
  /**
   * Generates the request for a determinated id
   *
   * @param int $id
   * @return array
   */
  public function req($id) {
    $post = get_post($id);
    $request['event_id'] = $post->ID;
    $request['v_what'] = $post->post_title;
    $request['content'] = $post->post_content;
    $request['v_excerpt'] = $post->post_excerpt;
    $request['v_author'] = $post->post_author;
    $request['v_pings'] = $post->ping_status;
    $request['v_comments'] = $post->comment_status;
    $request['v_calendar'] = wp_get_post_categories ($post->ID);

    $post = get_post_custom($post->ID);
    $request['v_where'] = $post['_where'][0];
    $request['v_destak'] = $post['_destak'][0];
    $request['v_when_start_date'] = date('Y-m-d', $post['_start'][0]);
    $request['v_when_start_time'] = date('H:i', $post['_start'][0]);
    $request['v_when_end_date'] = date('Y-m-d', $post['_end'][0]);
    $request['v_when_end_time'] = date('H:i', $post['_end'][0]);
    $request['v_all_day'] = $post['_all_day'][0] ? ' checked="checked"' : '';
    $request['repeat'] = $post['_repeat'][0];
    $tags = get_tags();
    $ttags = array ();
    foreach ($tags as $item)
      $ttags[] = $item->name;
    $request['v_tags'] = implode (',', $ttags);

    switch ($request['repeat']) {
      case 'daily':
        $request['repeat_daily'] = 'daily';
        $request['repeat_daily_every'] = $post['_every'][0];
        break;
      case 'weekly':
        $request['repeat_weekly'] = 'weekly';
        $request['repeat_weekly_every'] = $post['_every'][0];
        foreach (explode(',',$post['_every_on'][0]) as $v)
          $request['v_repeat_weekly_on_'.$v] = $v;
        break;
      case 'monthly':
        $request['repeat_monthly'] = 'monthly';
        $request['repeat_monthly_every'] = $post['_every'][0];
        $request['repeat_monthly_by'] = $post['_repeat_on'][0];
        break;
      case 'yearly':
        $request['repeat_yearly'] = 'yearly';
        $request['repeat_yearly_every'] = $post['_every'][0];
        break;
    }
    $request['range'] = $post['_range'][0];
    $request['v_range_ends_until'] = $post['_range_until'][0];
    return $request;
  }
  /**
   * Calls the save function ussing the global $_REQUEST
   *
   */
  public function saver () {
    if ($_REQUEST['repeat']) {
      $_REQUEST['v_all_day'] = $_REQUEST['v_all_day'] == 'v_all_day';
      $r=Agenda::saveEvent(
        (int) $_REQUEST['event_id'],
        $_REQUEST['v_what'],
        $_REQUEST['v_when_start_date'] . ' ' . $_REQUEST['v_when_start_time'],
        $_REQUEST['v_when_end_date'] . ' ' . $_REQUEST['v_when_end_time'],
        $_REQUEST['v_all_day'],
        $_REQUEST['v_where'],
        $_REQUEST['content'],
        $_REQUEST
      );
      $_REQUEST = array();
      return $r;
    }
  }
  /**
   * Makes a event
   *
   * @param string $what
   * @param string $from No formato YYYY-NN-DD HH:MM
   * @param string $to No formato YYYY-NN-DD HH:MM
   * @param string $where
   * @param string $desc
   * @param array $repeat Array gerado pelo post contendo o repeat do evento
   *
   * @return false|int Returns integer (ID of the post) if the post has been created
   */
  public function saveEvent ($id = 0, $what, $from, $to, $all_day=false, $where, $desc, $repeat) {
    global $event_errors;
    $event_errors = array();
    # User can do this?!
    if (!current_user_can( 'edit_posts' ))
      $event_errors[] = __('You are not allowed to create posts or drafts on this blog.', 'agenda');
    if (!current_user_can('edit_others_posts') )
      $event_errors[] = __('You are not allowed to post as this user.', 'agenda');
    # Working whit date
    $from = trim($from);
    $to = trim($to) ? trim($to) : trim($from);
    foreach (array ('from', 'to') as $item)
      if (!${'date_'.$item} = Agenda::_isValidDate($$item))
        $event_errors[] = __("Worng value on <strong>$item date</strong>.", 'agenda');

    if ($all_day) {
      foreach (array ('from', 'to') as $item) {
        $$item = vsprintf('%s-%s-%s 00:00', ${'date_'.$item}['time']);
        ${'date_'.$item} = Agenda::_isValidDate($$item);
      }
    }

    if ($date_from['microtime'] > $date_to['microtime'])
      $event_errors[] = $from . ' > ' . $to . __(': <strong>from</strong> date is larger than <strong>to</strong> date.', 'agenda');

    if ($repeat['submit']) $status = $id ? get_post($id)->post_status : 'draft';
    else $status = 'publish';

    if (count($event_errors)) return false;

    # Posting...
    $post = array(
      'post_title'     => $what ? $what : __('(No title)', 'agenda'),
      'post_status'    => $status,
      'post_content'   => $desc,
      'post_category'  => array(get_option('agenda_category'), $repeat['v_calendar']),
      'tags_input'     => $repeat['v_tags'],
      'post_excerpt'   => $repeat['v_excerpt'],
      'ping_status'    => $repeat['v_pings'],
      'comment_status' => $repeat['v_comments'],
    );
    # New or Update
    if ($id) {$post['ID']=$id;wp_update_post($post);}
    else {$id=wp_insert_post($post);}

    # Common Metas
    if ($all_day) Agenda::_putMeta($id, '_all_day', '1');
    else delete_post_meta($id, '_all_day', '1');

    $itens = array (
        '_start' => $date_from['microtime'],
        '_end'    => $date_to['microtime'],
        '_where'  => $where,
        '_destak' => $repeat['v_destak'],
      );
    foreach ($itens as $key => $value)
      Agenda::_putMeta($id, $key, $value);

    # The repeat
    if (!in_array($repeat['repeat'], array ('daily', 'weekly', 'monthly', 'yearly', 'other')) || !in_array($repeat['range'], array('never', 'until'))) {
      $repeat['repeat'] = 'no_repeat';
      $repeat['range'] = 'never';
    } else {
      if ($repeat['range'] == 'until' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $repeat['v_range_ends_until'])) {
        $repeat['repeat'] = 'no_repeat';
        $repeat['range'] = 'never';
      }
    }
    # Daily
    if ($repeat['repeat'] == 'daily') {
      if (!preg_match('@^\d+$@', $repeat['repeat_daily_every'])) {
        $repeat['repeat'] = 'no_repeat';
        $repeat['range'] = 'never';
      } else {
        Agenda::_putMeta($id, '_repeat', 'daily');
        Agenda::_putMeta($id, '_every', $repeat['repeat_daily_every']);
      }
    }
    # Weekly
    if ($repeat['repeat'] == 'weekly') {
      if (!preg_match('@^\d+$@', $repeat['repeat_weekly_every'])) {
        $repeat['repeat'] = 'no_repeat';
        $repeat['range'] = 'never';
      } else {
        $repeats_on=array();
        foreach (Agenda::_week() as $day)
          if ($repeat['v_repeat_weekly_on_' . $day])
            $repeats_on[] = $day;
        Agenda::_putMeta($id, '_repeat', 'weekly');
        Agenda::_putMeta($id, '_every', $repeat['repeat_weekly_every']);
        Agenda::_putMeta($id, '_every_on', implode(',', $repeats_on));
      }
    }
    # Monthly
    if ($repeat['repeat'] == 'monthly') {
      if (!preg_match('@^\d+$@', $repeat['repeat_monthly_every'])) {
        $repeat['repeat'] = 'no_repeat';
        $repeat['range'] = 'never';
      } else {
        Agenda::_putMeta($id, '_repeat', 'monthly');
        Agenda::_putMeta($id, '_every', $repeat['repeat_monthly_every']);
        Agenda::_putMeta($id, '_repeat_on', $repeat['repeat_monthly_by']);
      }
    }
    # Yearly
    if ($repeat['repeat'] == 'yearly') {
      if (!preg_match('@^\d+$@', $repeat['repeat_yearly_every'])) {
        $repeat['repeat'] = 'no_repeat';
        $repeat['range'] = 'never';
      } else {
        Agenda::_putMeta($id, '_repeat', 'yearly');
        Agenda::_putMeta($id, '_every', $repeat['repeat_yearly_every']);
      }
    }
    # The Repeat
    if ($repeat['repeat'] == 'no_repeat') {
      Agenda::_putMeta($id, '_repeat', 'no_repeat');
    } else {
      Agenda::_putMeta($id, '_range', $repeat['range']);
      Agenda::_putMeta($id, '_range_until', $repeat['range'] == 'until' ? $repeat['v_range_ends_until'] : '');
    }
    return $id;
  }
  /**
   * Tratate an event, used to retrive all info from event
   *
   * @param event $event Reference to an event
   */
  public function tratare (&$event) {
    global $event_order;
    if (empty($event_order)) $event_order = array ();
    if ($event['all_day']) {
      $event['when_start_time'] = '00:00';
      $event['when_end_time'] = '24:00';
    }

    list($year, $month, $day) = split('-', $event['when_start_date']);
    list($hour, $minute) = split(':', $event['when_start_time']);
    $start = mktime($hour, $minute, 0, $month, $day, $year);

    list($year, $month, $day) = split('-', $event['when_end_date']);
    list($hour, $minute) = split(':', $event['when_end_time']);
    $end = mktime($hour, $minute, 0, $month, $day, $year);

    $duration = $end-$start;
    $event['duration'] = $duration;

    if ($event['repeat'] == 'no_repeat') $event['ends_on'] = 'once';
    elseif ($event['range'] == 'never') $event['ends_on'] = 'never';
    else {
      list($year, $month, $day) = split('-', $event['range_ends_until']);
      $event['ends_on'] = mktime(0,1,0,$month,$day,$year);
    }
    list($hour, $minute) = split(':', $event['when_start_time']);
    list($year, $month, $day) = split('-', $event['when_start_date']);
    if ($event['repeat'] == 'monthly' && $event['repeat_monthly_by'] == 'week') {
      $r_d_week = date('w', $start);
      $l_month = mktime(0, 0, 0, $month, 1, $year);
      $ld_month = date('t', $l_month);
      for ($i=1,$w=1;$i<$ld_month;$i++) {
        $tmp=mktime($hour,$minute,0,$month,$i,$year);
        if ($i>1 && date('w',$tmp) == 0) $w++;
        if ($i==$day) {$r_w_weel = $w;break;}
      }
    }
    if ($event['repeat'] == 'weekly') {
      $w_days = array();
      foreach (array ('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') as $item)
        if (isset($event['repeat_weekly_on_'.$item]))
          $w_days[] = $item;
      $w_time = $start;
      $event_order[] =  "{$start}|{$event['event_id']}";
    }
    for ($i=0; $i < 100; $i++) {
      if ($event['ends_on'] == 'once') {
        $time =  $start;
      }
      if ($event['repeat'] == 'daily') {
        $repeat_time = $i*($event['repeat_daily_every']-1);
        $time = mktime($hour, $minute, $duration*$i, $month, $day+$repeat_time, $year);
      }
      if ($event['repeat'] == 'yearly') {
        $time = mktime($hour, $minute, 0, $month, $day, $year+$i*$event['repeat_yearly_every']);
        if(!$time) break;
      }
      if ($event['repeat'] == 'monthly') {
        if ($event['repeat_monthly_by'] == 'month') {
          $time = mktime($hour, $minute, 0, $month+$i*$event['repeat_monthly_every'], $day, $year);
        }
        if ($event['repeat_monthly_by'] == 'week') {
          $r_month = $month + ($i*$event['repeat_monthly_every']);
          $t_month = mktime(0, 0, 0, $r_month, 1, $year);
          $d_month = date('t', $t_month);
          for ($j=1,$w=1;$j<$d_month;$j++) {
            $tmp=mktime($hour,$minute,0,$month+$i,$j,$year);
            if($j>1&&date('w',$tmp)==0)$w++;
            if ($w<$r_w_week) continue;
            if ($r_d_week == date('w', $tmp)) {$time = $tmp;break 1;}
          }
        }
      }
      if ($event['repeat'] == 'weekly') {
        for ($j=0,$w_time+=(24*60*60);$j<7;$j++,$w_time+=(24*60*60)){
          if (in_array(strtolower(date('l', $w_time)), $w_days)) {
            $time=$w_time;
            break 1;
          }
        }
      }
      if (preg_match('@^\d+$@', $event['ends_on']) && $time > $event['ends_on']) break;
      $event_order[] =  "{$time}|{$event['event_id']}";
      if ($event['ends_on'] == 'once') break;
    }
  }
  /**
   * Returns an array (list) of all events registreds in your blog
   *
   * @param bool $only_destak If true, return only hightlighed events
   * @param string $post_status The status of retrived events (can be publish or draft)
   */
  public function the_events ($only_destak=false, $post_status='publish') {
    global $event_order;
    if (gettype($event_order)!=='array')
      $event_order=array ();
    $args = array (
      'numberposts' => -1,
      'category'    => get_option('agenda_category'),
      'post_status' => $post_status,
    );
    $posts = get_posts($args);
    $return = array();
    foreach ($posts as $post) {
      $tmp = Agenda::req($post->ID);
      if($only_destak && !$tmp['v_destak']) continue;
      foreach ($tmp as $k=>$v) {
        if (preg_match('@^v_@', $k)) {
          $tmp[preg_replace('@^v_@', '', $k)] = $v;
          unset ($tmp[$k]);
        }
      }
      if ($tmp['all_day']) {
        $tmp['all_day'] = true;
        unset($tmp['when_start_time'], $tmp['when_end_time']);
      }
      if ($tmp['repeat']=='no_repeat') {
        unset($tmp['range'], $tmp['range_ends_until']);
      }
      $return[] = $tmp;
    }
    array_filter($return, array('Agenda', 'tratare'));
    sort($event_order);
    return $return;
  }
  /**
   * Return the next events, it calculates all repeats
   *
   * @param int   $next         Number of next events
   * @param bool  $only_destak  If true, return only hightlighed events
   * @param string $post_status The status of retrived events (can be publish or draft)
   */
  public function next_events ($next = 5,$only_destak=false, $post_status='publish') {
    global $event_order, $_the_agenda_events;
    if (gettype($event_order)!=='array')
      $event_order=array ();
    $events = Agenda::the_events($only_destak, $post_status);
    $now = time();
    $return = array();
    foreach ($event_order as $item) {
      list($time,$id)=explode('|', $item);
      if ($now>$time) continue;
      $e=Agenda::req($id);
      $return[] = array('utime'=>$time,'date'=>date('d-m-Y H:i', $time),'id'=>$id,'event'=>$e);
      if ($next<=++$i) break;
    }
    $_the_agenda_events = $return;
    return $return;
  }
  /**
   * Returns a list of events in determinated month or year
   * Pass your params as wp_string or assigned array:
   * <code>Agenda::events_on('month=5&year=2006')</code>
   * is the same as:
   * <code>Agenda::events_on(array('month'=>5,'year'=>2006))</code>
   *
   * you can pass a empty month to get events of all year.
   *
   * Default arguments is:
   * <code>array ('month'=>date('m'), 'year'=>date('Y'), 'post_status' => 'publish')</code>
   *
   * @param string|array $args Arguments to configure the return
   * @return array
   */
  public function events_on ($args) {
    global $event_order, $_the_agenda_events;
    $defaults = array(
      'month'       => date('m'),
      'year'        => date('Y'),
      'post_status' => 'publish',
    );
    $args = wp_parse_args($args, $defaults);
    extract($args); $month=(int) $month; $year=(int) $year;
    if (!is_int($month) || (!is_int($year) && $year!=false)) return;
    if (gettype($event_order)!=='array') $event_order=array ();
    $events = Agenda::the_events($only_destak, $post_status);
    $return = array();
    if ($month) {
      $start = mktime(0,0,0,$month, 1, $year);
      $end   = mktime(0,0,0,$month+1, 0, $year);
    } else {
      $start = mktime(0,0,0,1, 1, $year);
      $end   = mktime(0,0,0,1, 0, $year+1);
    }
    foreach ($event_order as $item) {
      list($time,$id)=explode('|', $item);
      $e=Agenda::req($id);
      if ($time<$start || $time>$end) continue;
      $return[] = array('utime'=>$time,'date'=>date('d-m-Y H:i', $time),'id'=>$id,'event'=>$e);
    }
    $_the_agenda_events = $return;
    return $return;
  }
}

/**
 * Generates a list of next events marked in your agenda
 * Pass your params as wp_string or assigned array:
 * <code>agenda_list('next=5&only_destak=1')</code>
 * is the same as:
 * <code>agenda_list(array('next'=>5,'only_destak'=>true))</code>
 *
 * Params:
 * - (int) next         : Number of next events to show (default: 5)
 * - (bool) only_destak : If true, show only highlighted events (default: true)
 * - (bool) show_date   : If true, show the date of each event (default: true)
 * - (string) before    : Put it before each event (default: '<li>')
 * - (string) after     : Put it after each event (default: '</li>')
 * - (bool) print       : Prints the output (default: true)
 *
 * By default, the function prints the generated string, but you can silencity it and
 * manipulate the same string. It is returned.
 *
 * @param string|array $args
 * @return string
 */
function agenda_list ($args) {
  $default_options = array (
      'next'        => 5,
      'only_destak' => true,
      'show_date'   => true,
      'before'      => '<li>',
      'after'       => '</li>',
      'print'       => true,
    );
  $args = wp_parse_args($args, $defaults);extract($args);

  $e = Agenda::next_events($next,$only_destak);
  $itens=array ();
  foreach ($e as $item) {
    $p = get_post($item['id']);
    $itens[] = sprintf ('%s<a href="%s" title="%s" class="vevent">%s%s%s%s</a>%s',
      $before,
      $p->guid,
      $item['event']['v_what'],
      (!$only_destak ? ($item['event']['v_destak'] == '1' ? '<strong>' : '') : ''),
      $show_date ? '<abbr class="dtstart" title="'.str_replace(' ', 'T', str_replace('/', '', $item['date'])).'">'.$item['date'] . '</abbr> - ' : '',
      "<span class=\"summary\">{$item['event']['v_what']}</span>",
      (!$only_destak ? ($item['event']['v_destak'] == '1' ? '</strong>' : '') : ''),
      $after
    );
  }
  $return = implode("\n", $itens);
  if ($print) print ($return);
  return $return;
}

/**
 * Generates an list of next events, you can use it to generate a page of your agenda
 * It prints something similar this:
 * <code>
 *   <div class="even">
 *     <h3>2008-05-12</h3>
 *     <ul>
 *       <li><a href="link-to-post1" title="My Event">My Event</a></li>
 *       <li><a href="link-to-post2" title="My Simple Event">My Simple Event</a></li>
 *       <li><a href="link-to-post3" title="My New Event">My New Event</a></li>
 *       <li><a href="link-to-post4" title="My Classic Event">My Classic Event</a></li>
 *     </ul>
 *   </div>
 * </code>
 *
 * @param int $next Number of max next events to show. (default: 25)
 * @param string $title_tag The tag to show the date (default: h3)
 * @param bool $print If true, output the returned string
 * @return string
 */
function agenda_compromissos ($next=25, $title_tag='h3', $print=true) {
  $e = Agenda::next_events($next);
  $itens=array ();
  foreach ($e as $item) {
    $p = get_post($item['id']);
    $itens[date('d-m-Y', $item['utime'])][] = sprintf ('<li><a href="%s" title="%s">%s</a></li>',
      $p->guid,
      $item['event']['v_what'],
      $item['event']['v_what']
    );
  }
  $r='';
  $i=0;
  foreach ($itens as $key => $value) {
    $i++;
    $even_odd='event_'.($i%2?'even':'odd');
    $r.="<div class=\"$even_odd\">\n\n<$title_tag>$key</$title_tag><ul>";
    foreach ($value as $item)
      $r.="$item";
    $r.='</ul></div>';
  }
  if ($print) print ($r);
  return $r;
}
/**
 * Like agenda_compromissos(), it generates an list of events
 * The basic diference is that agenda_events uses a determinated month and year
 *
 * Pass your params as wp_string or assigned array:
 * <code>agenda_list('next=5&only_destak=1')</code>
 * is the same as:
 * <code>agenda_list(array('next'=>5,'only_destak'=>true))</code>
 *
 * Params:
 * - int month          : The month to show. If passed blank it ignores the month and uses all the year. ( default: date('m') )
 * - int year           : The year to show. ( default: date('Y') )
 * - string title_tag   : The tag to show the date (default: h3)
 * - bool print         : If true, output the returned string (default: true)
 *
 * @see agenda_compromissos()
 *
 * @param string|array $args The args to manipulate your output
 * @return string
 */
function agenda_events($args='') {
  $defaults = array(
    'month' => date('m'),
    'year' => date('Y'),
    'title_tag' => 'h3',
    'print' => '1'
  );
  $args = wp_parse_args($args, $defaults);
  extract($args); $month=(int) $month; $year=(int) $year;
  $events = Agenda::events_on($args);
  $itens=array ();
  foreach ($events as $item) {
    $p = get_post($item['id']);
    $itens[date('d-m-Y', $item['utime'])][] = sprintf ('<li><a href="%s" title="%s">%s</a></li>',
      $p->guid,
      $item['event']['v_what'],
      $item['event']['v_what']
    );
  }
  $r='';
  $i=0;
  foreach ($itens as $key => $value) {
    $even_odd='event_'.(++$i%2?'even':'odd');
    $r.="<div class=\"$even_odd\">\n\n<$title_tag>$key</$title_tag><ul>";
    foreach ($value as $item)
      $r.="$item";
    $r.='</ul></div>';
  }
  if ($print) print ($r);
  return $r;
}
/**
 * Using the_agenda_loop you generates a $the_event object that have all atributes about the event
 * Use it in combination to Agenda::next_events(); or Agenda::events_on();
 *
 * @see Agenda::next_events()
 * @see Agenda::events_on()
 *
 * <code>
 * Agenda::events_on('month=5,year=2008');
 * while (the_agenda_loop()) {
 *   print "<h1>{$the_event->what}</h1>";
 * }
 * </code>
 *
 * $the_event is an standard object like this:
 *
 *   stdClass Object (
 *     [event_id] => 36
 *     [content] => Um exemplo de evento simples com a marcação necessária.
 *     [repeat] => daily
 *     [repeat_daily] => daily
 *     [repeat_daily_every] => 3
 *     [range] => never
 *     [what] => Meu simples evento
 *     [excerpt] => Meu excerpt, caso seja necessário.
 *     [author] => 1
 *     [pings] => open
 *     [comments] => open
 *     [calendar] => Array()
 *     [where] =>
 *     [destak] => 0
 *     [when_start_date] => 2008-09-15
 *     [when_start_time] => 00:00
 *     [when_end_date] => 2008-09-16
 *     [when_end_time] => 00:00
 *     [all_day] => 1
 *     [tags] => e mais tag,tag
 *     [range_ends_until] =>
 *   )
 *
 */
function the_agenda_loop () {
  global $_the_agenda_events, $the_event;
  if ('array' !== gettype($_the_agenda_events)) $_the_agenda_events = array ();
  $event = array_shift(&$_the_agenda_events);
  if (!$event) return false;

  $event = $event['event'];
  foreach ($event as $key => $value) {
    if (strpos($key, 'v_') === 0) {
      $event[substr($key, 2)] = $value;
      unset ($event[$key]);
    }
  }
  $event['all_day'] = (bool) trim($event['all_day']);
  $tmp = array ();
  foreach ($event['calendar'] as $item) {
    if ($value !== get_option ('agenda_category')) continue;
    $tmp[] = get_category($item);
  }
  $event['calendar'] = $tmp;

  $the_event = (object) $event;
  return $event;
}

$ucmPluginFile = substr(strrchr(dirname(__FILE__),DIRECTORY_SEPARATOR),1).DIRECTORY_SEPARATOR.basename(__FILE__);
register_activation_hook($ucmPluginFile, array('Agenda','install'));
add_action('init', array ('Agenda', 'init'), 10);