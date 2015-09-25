<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls
 */
require_once '../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

$current_user_id = api_get_user_id();
switch ($action) {
    case 'add_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        if (isset($_POST['is_my_friend'])) {
            $relation_type = USER_RELATION_TYPE_FRIEND; //My friend
        } else {
            $relation_type = USER_RELATION_TYPE_UNKNOW; //Unknown contact
        }

        if (isset($_POST['friend_id'])) {
            $my_current_friend  = $_POST['friend_id'];
            UserManager::relate_users($current_user_id, $my_current_friend, $relation_type);
            UserManager::relate_users($my_current_friend, $current_user_id, $relation_type);
            SocialManager::invitation_accepted($my_current_friend, $current_user_id);
            Display::display_normal_message(api_xml_http_response_encode(get_lang('AddedContactToList')));
        }
        break;
    case 'deny_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }


        if (isset($_POST['is_my_friend'])) {
            $relation_type = USER_RELATION_TYPE_FRIEND;//my friend
        } else {
            $relation_type = USER_RELATION_TYPE_UNKNOW;//Contact unknown
        }
        if (isset($_POST['denied_friend_id'])) {
            SocialManager::invitation_denied($_POST['denied_friend_id'], $current_user_id);
            Display::display_confirmation_message(api_xml_http_response_encode(get_lang('InvitationDenied')));
        }
        break;
    case 'delete_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $my_delete_friend = intval($_POST['delete_friend_id']);
        if (isset($_POST['delete_friend_id'])) {
            SocialManager::remove_user_rel_user($my_delete_friend);
        }
        break;
    case 'show_my_friends':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $user_id	= api_get_user_id();
        $name_search= Security::remove_XSS($_POST['search_name_q']);
        $number_friends = 0;

        if (isset($name_search) && $name_search != 'undefined') {
            $friends = SocialManager::get_friends($user_id, null, $name_search);
        } else {
            $friends = SocialManager::get_friends($user_id);
        }

        $friend_html = '';
        $number_of_images = 8;

        $number_friends = count($friends);
        if ($number_friends != 0) {
            $number_loop   = ($number_friends/$number_of_images);
            $loop_friends  = ceil($number_loop);
            $j=0;
            $friend_html.= '<ul class="thumbnails">';
            for ($k=0; $k<$loop_friends; $k++) {
                if ($j==$number_of_images) {
                    $number_of_images=$number_of_images*2;
                }
                while ($j<$number_of_images) {
                    if (isset($friends[$j])) {
                        $friend_html.='<li class="span2">';
                        $friend = $friends[$j];
                        $user_name = api_xml_http_response_encode($friend['firstName'].' '.$friend['lastName']);
                        $userPicture = UserManager::getUserPicture($friend['friend_user_id']);
                        $friend_html.='<div class="thumbnail" onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$friends[$j]['friend_user_id'].'>';
                        $friend_html.='<img src="'.$userPicture.'" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$user_name.'" />';
                        $friend_html.='<div class="caption">';
                        $friend_html.='<a href="profile.php?u='.$friend['friend_user_id'].'"><h5>'.$user_name.'</h5></a>';
                        $friend_html.='<p><button class="btn btn-danger" onclick="delete_friend(this)" id=img_'.$friend['friend_user_id'].'>'.get_lang('Delete').'</button></p>';
                        $friend_html.='</div>';
                        $friend_html.='</div>';

                        $friend_html.='</li>';
                    }
                    $j++;
                }
            }
            $friend_html.='</ul>';
        }
        echo $friend_html;
        break;
    case 'toogle_course':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $user_id = intval($_SESSION['social_user_id']);

        if ($_POST['action']) {
            $action = $_POST['action'];
        }

        switch ($action) {
            case 'load_course':
                $course_id =  intval($_POST['course_code']); // the int course id
                $course_info = api_get_course_info_by_id($course_id);
                $course_code = $course_info['code'];

                if (api_is_user_of_course($course_id, api_get_user_id())) {
                    //------Forum messages
                    $forum_result = get_all_post_from_user($user_id, $course_code);
                    $all_result_data = 0;
                    if ($forum_result !='') {
                        echo '<div id="social-forum-main-title">';
                        echo api_xml_http_response_encode(get_lang('Forum'));
                        echo '</div>';

                        echo '<div style="background:#FAF9F6; padding:0px;" >';
                        echo api_xml_http_response_encode($forum_result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }

                    //------Blog posts
                    $result = get_blog_post_from_user($course_code, $user_id);

                    if (!empty($result)) {
                        api_display_tool_title(api_xml_http_response_encode(get_lang('Blog')));
                        echo '<div style="background:#FAF9F6; padding:0px;">';
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }

                    //------Blog comments
                    $result = get_blog_comment_from_user($course_code, $user_id);
                    if (!empty($result)) {
                        echo '<div  style="background:#FAF9F6; padding-left:10px;">';
                        api_display_tool_title(api_xml_http_response_encode(get_lang('BlogComments')));
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }
                    if ($all_result_data == 0) {
                        echo api_xml_http_response_encode(get_lang('NoDataAvailable'));
                    }

                } else {
                        echo '<div class="clear"></div><br />';
                        api_display_tool_title(api_xml_http_response_encode(get_lang('Details')));
                        echo '<div style="background:#FAF9F6; padding:0px;">';
                        echo api_xml_http_response_encode(get_lang('UserNonRegisteredAtTheCourse'));
                        echo '<div class="clear"></div><br />';
                        echo '</div>';
                        echo '<div class="clear"></div><br />';
                }
                break;
            case 'unload_course':
                break;
            default:
                break;
        }
        break;
    case 'listWallMessage':
        $start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) - 1 : 0;
        $length = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10;
        $userId = isset($_REQUEST['u']) ? intval($_REQUEST['u']) : api_get_user_id();
        $friendId = $userId;
        $array = SocialManager::getWallMessagesPostHTML($userId, $friendId, null, $length, $start);
        if (!empty($array)) {
            ksort($array);
            $html = '';
            for($i = 0; $i < count($array); $i++) {
                $post = $array[$i]['html'];
                $comment = SocialManager::getWallMessagesHTML($userId, $friendId, $array[$i]['id']);
                $html .= '<div class="panel panel-info"><div class="panel-body">'.$post.$comment.'</div></div>';
            }
            $html .= Display::div(
                Display::url(
                    get_lang('SeeMore'),
                    api_get_self() . '?u=' . $userId . '&a=listWallMessage&start=' .
                    ($start + $length + 1) . '&length=' . $length,
                    array(
                        'class' => 'nextPage',
                    )
                ),
                array(
                    'class' => 'next',
                )
            );
            echo $html;
        }
        break;
        // Read the Url using OpenGraph and returns the hyperlinks content
    case 'readUrlWithOpenGraph':
        $url = isset($_POST['social_wall_new_msg_main']) ? $_POST['social_wall_new_msg_main'] : '';
        $html = '';
        if (SocialManager::verifyUrl($url) == true){
            $html = Security::remove_XSS(SocialManager::readContentWithOpenGraph($url));
        }
        echo $html;
        break;
    default:
        echo '';
}
exit;
