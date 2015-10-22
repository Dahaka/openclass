<?php
/* ========================================================================
 * Open eClass 2.8
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

require_once $GLOBALS['webDir'] . '/include/lib/modalboxhelper.class.php';

class MultimediaHelper {

    /**
     * Construct a proper <a href> html tag for opening media files in a modal box.
     *
     * @param  MediaResource $mediaRsrc
     * @return string
     */
    public static function chooseMediaAhref($mediaRsrc) {
        return self::chooseMediaAhrefRaw(
                $mediaRsrc->getAccessURL(),
                $mediaRsrc->getPlayURL(),
                $mediaRsrc->getTitle(),
                $mediaRsrc->getPath());
    }
    
    /**
     * Construct a proper <a href> html tag for opening media files in a modal box.
     *
     * @param  string $mediaDL   - access or download url
     * @param  string $mediaPlay - media playback url
     * @param  string $title     - media file title
     * @param  string $filename  - media filename
     * @return string
     */
    public static function chooseMediaAhrefRaw($mediaDL, $mediaPlay, $title, $filename) {
        $title = q($title);
        $filename = q($filename);
        $ahref = "<a href='$mediaDL' class='fileURL' target='_blank' title='$title'>". $title ."</a>";
        $class = '';
        $extraParams = '';

        if (self::isSupportedFile($filename)) {
            if (file_exists( ModalBoxHelper::getShadowboxDir() )) {
                $class = 'shadowbox';
                $extraParams = (self::isSupportedImage($filename)) 
                    ? "rel='shadowbox'"
                    : "rel='shadowbox;width=". 
                        ModalBoxHelper::getShadowboxWidth()
                        .";height=". 
                        ModalBoxHelper::getShadowboxHeight() . 
                        ModalBoxHelper::getShadowboxPlayer($filename) ."'";
            } else if (file_exists( ModalBoxHelper::getFancybox2Dir() ))
                $class = (self::isSupportedImage($filename)) ? 'fancybox' : 'fancybox iframe';
            else if (file_exists( ModalBoxHelper::getColorboxDir() ))
                $class = (self::isSupportedImage($filename)) ? 'colorbox' : 'colorboxframe';
            
            $ahref = "<a href='$mediaPlay' class='$class fileURL' $extraParams title='$title'>".$title."</a>";
            if (self::isSupportedImage($filename))
                $ahref = "<a href='$mediaDL' class='$class fileURL' title='$title'>".$title."</a>";
        }

        return $ahref;
    }

    /**
     * Construct a proper <a href> html tag for opening media links in a modal box.
     *
     * @global string $userServer
     * @global string $course_code
     * @param  MediaResource $mediaRsrc
     * @return string
     */
    public static function chooseMedialinkAhref($mediaRsrc) {
        $title = q($mediaRsrc->getTitle());
        $ahref = "<a href='" . q($mediaRsrc->getPath()) . "' class='fileURL' target='_blank' title='$title'>". $title ."</a>";

        if (self::isEmbeddableMedialink($mediaRsrc->getPath())) {
            $class = '';
            $extraParams = '';
            
            if (file_exists( ModalBoxHelper::getShadowboxDir() )) {
                $class = 'shadowbox';
                $extraParams = "rel='shadowbox;width=". 
                    ModalBoxHelper::getShadowboxWidth() 
                    .";height=". 
                    ModalBoxHelper::getShadowboxHeight() ."'";
            } else if (file_exists(ModalBoxHelper::getFancybox2Dir() ))
                $class = 'fancybox iframe';
            else if (file_exists( ModalBoxHelper::getColorboxDir() ))
                $class = 'colorboxframe';
            
            $ahref = "<a href='" . $mediaRsrc->getPlayURL() . "' class='$class fileURL' $extraParams title='$title'>$title</a>";
        }

        return $ahref;
    }
    
    /**
     * Construct a proper <object> html tag for each type of media.
     *
     * @global MediaResource $mediaRsrc
     * @return string
     */
    public static function mediaHtmlObject($mediaRsrc) {
        return self::mediaHtmlObjectRaw(
                $mediaRsrc->getAccessURL(), 
                $mediaRsrc->getAccessURL(), 
                $mediaRsrc->getPath());
    }
    
    /**
     * Construct a proper <object> html tag for each type of media.
     *
     * @global string $urlAppend
     * @param  string $mediaPlay
     * @param  string $mediaDL
     * @param  string $mediaPath
     * @return string
     */
    public static function mediaHtmlObjectRaw($mediaPlay, $mediaDL, $mediaPath = null) {
        global $urlAppend;

        if ($mediaPath == null)
            $mediaPath = $mediaPlay;
        $extension = get_file_extension($mediaPath);

        $ret = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html><head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';

        $startdiv = '</head><body style="font-weight: bold"><div align="center">';
        $enddiv = '</div></body>';

        switch($extension) {
            case "asf":
            case "avi":
            case "wm":
            case "wmv":
            case "wma":
                $ret .= $startdiv;
                if (self::isUsingIE())
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6">
                                <param name="url" value="'.$mediaPlay.'">
                                <param name="autostart" value="1">
                                <param name="uimode" value="full">
                                <param name="wmode" value="transparent">
                            </object>';
                else
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                type="video/x-ms-wmv"
                                data="'.$mediaPlay.'">
                                <param name="autostart" value="1">
                                <param name="showcontrols" value="1">
                                <param name="wmode" value="transparent">
                            </object>';
                $ret .= $enddiv;
                break;
            case "dv":
            case "mov":
            case "moov":
            case "movie":
            case "mp4":
            case "mpg":
            case "mpeg":
            case "3gp":
            case "3g2":
            case "m2v":
            case "aac":
            case "m4a":
                $ret .= $startdiv;
                if (self::isUsingIE())
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'" kioskmode="true"
                                classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
                                codebase="http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0">
                                <param name="src" value="'.$mediaPlay.'">
                                <param name="scale" value="aspect">
                                <param name="controller" value="true">
                                <param name="autoplay" value="true">
                                <param name="wmode" value="transparent">
                            </object>';
                else
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'" kioskmode="true"
                                type="video/quicktime"
                                data="'.$mediaPlay.'">
                                <param name="src" value="'.$mediaPlay.'">
                                <param name="scale" value="aspect">
                                <param name="controller" value="true">
                                <param name="autoplay" value="true">
                                <param name="wmode" value="transparent">
                            </object>';
                $ret .= $enddiv;
                break;
            case "flv":
            case "f4v":
            case "m4v":
            case "mp3":
                $ret .= "<script type='text/javascript' src='{$urlAppend}/js/flowplayer/flowplayer-3.2.12.min.js'></script>";
                $ret .= $startdiv;
                if (self::isUsingIOS())
                    $ret .= '<br/><br/><a href="'.$mediaDL.'">Download or Stream media</a>';
                else {
                    $ret .= '<div id="flowplayer" style="display: block; width: '. self::getObjectWidth() .'px; height: '. self::getObjectHeight() .'px;"></div>
                             <script type="text/javascript">
                                 flowplayer("flowplayer", {
                                     src: "'.$urlAppend.'/js/flowplayer/flowplayer-3.2.16.swf",
                                     wmode: "transparent"
                                     }, {
                                     clip: {
                                         url: "'.$mediaPlay.'",';
                    // flowplayer needs to see a pattern of name.mp3 in order to stream it
                    if ($extension == 'mp3')
                        $ret .= '        type: "audio",';
                    $ret .= '            scaling: "fit"
                                     },
                                     canvas: {
                                         backgroundColor: "#000000",
                                         backgroundGradient: "none"
                                     }
                                 });
                             </script>';
                }
                $ret .= $enddiv;
                break;
            case "swf":
                $ret .= $startdiv;
                if (self::isUsingIE())
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                 classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000">
                                 <param name="movie" value="'.$mediaPlay.'"/>
                                 <param name="bgcolor" value="#000000">
                                 <param name="allowfullscreen" value="true">
                                 <param name="wmode" value="transparent">
                                 <a href="http://www.adobe.com/go/getflash">
                                    <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player"/>
                                 </a>
                             </object>';
                else
                    $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                 data="'.$mediaPlay.'"
                                 type="application/x-shockwave-flash">
                                 <param name="bgcolor" value="#000000">
                                 <param name="allowfullscreen" value="true">
                                 <param name="wmode" value="transparent">
                             </object>';
                $ret .= $enddiv;
                break;
            case "webm":
            case "ogv":
            case "ogg":
                $ret .= $startdiv;
                if (self::isUsingIE())
                    $ret .= '<a href="'.$mediaDL.'">Download media</a>';
                else
                    $ret .= '<video controls="" autoplay="" width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                 style="margin: auto; position: absolute; top: 0; right: 0; bottom: 0; left: 0;"
                                 name="media"
                                 src="'.$mediaPlay.'">
                             </video>';
                $ret .= $enddiv;
                break;
            default:
                $ret .= $startdiv;
                $ret .= '<a href="'.$mediaDL.'">Download media</a>';
                $ret .= $enddiv;
                break;
        }

        $ret .= '</html>';

        return $ret;
    }

    /**
     * Construct a proper <iframe> html tag for each type of medialink.
     *
     * @param  MediaResource $mediaRsrc
     * @return string
     */
    public static function medialinkIframeObject($mediaRsrc) {
        $mediaURL = q(urldecode(self::makeEmbeddableMedialink($mediaRsrc->getAccessURL())));
        $ret = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html><head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                </head>
                <body style="font-weight: bold">
                <div align="center">';

        $needEmbed = array_merge(self::getGooglePatterns(), self::getMetacafePatterns(), self::getMyspacePatterns());

        $gotEmbed = false;
        foreach ($needEmbed as $pattern) {
            if (preg_match($pattern, $mediaURL)) {
                $ret .= '<object width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'">
                             <param name="allowFullScreen" value="true"/>
                             <param name="wmode" value="transparent"/>
                             <param name="movie" value="'.$mediaURL.'"/>
                             <embed flashVars="playerVars=autoPlay=yes"
                                 src="'.$mediaURL.'"
                                 width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                                 allowFullScreen="true"
                                 allowScriptAccess="always"
                                 type="application/x-shockwave-flash"
                                 wmode="transparent">
                             </embed>
                         </object>';
                $gotEmbed = true;
            }
        }

        if (!$gotEmbed)
            $ret .='<iframe width="'. self::getObjectWidth() .'" height="'. self::getObjectHeight() .'"
                        src="'.$mediaURL.'" frameborder="0" allowfullscreen></iframe>';

        $ret .='</div></body></html>';

        return $ret;
    }

    /**
     * Whether the client uses Internet Explorer or not
     *
     * @return boolean
     */
    public static function isUsingIE() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        return (preg_match('/MSIE/i', $u_agent)) ? true : false;
    }
    
    /** Whether the client uses an iOS device or not
     * 
     * @return boolean
     */
    public static function isUsingIOS() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        return (preg_match('/iPhone/i', $u_agent) || 
                preg_match('/iPod/i', $u_agent) || 
                preg_match('/iPad/i', $u_agent)) ? true : false;
    }

    /**
     * Whether the image is supported or not
     *
     * @param  string  $filename
     * @return boolean
     */
    public static function isSupportedImage($filename) {
        return in_array(get_file_extension($filename), self::getSupportedImages());
    }

    /**
     * Whether the media (video or audio) is supported or not
     *
     * @param  string  $filename
     * @return boolean
     */
    public static function isSupportedMedia($filename) {
        return in_array(get_file_extension($filename), self::getSupportedMedia());
    }
    
    /**
     * Whether the file (video or audio or image) is supported or not
     * 
     * @param  string  $filename
     * @return boolean
     */
    public static function isSupportedFile($filename) {
        return (self::isSupportedMedia($filename) || self::isSupportedImage($filename));
    }

    /**
     * Whether the medialink can be embedded in a modal box
     *
     * @param  string $medialink
     * @return boolean
     */
    public static function isEmbeddableMedialink($medialink) {
        $supported = array_merge(self::getYoutubePatterns(), self::getVimeoPatterns(),
                                 self::getGooglePatterns(), self::getMetacafePatterns(),
                                 self::getMyspacePatterns(), self::getDailymotionPatterns());
        $ret = false;

        foreach ($supported as $pattern) {
            if (preg_match($pattern, $medialink))
                $ret = true;
        }

        return $ret;
    }

    /**
     * Convert known media link types to embeddable links
     *
     * @param  string $medialink
     * @return string
     */
    public static function makeEmbeddableMedialink($medialink) {
        $matches = null;
        
        foreach (self::getYoutubePatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]);
                $medialink = 'http://www.youtube.com/embed/'. $sanitized .'?hl=en&fs=1&rel=0&autoplay=1&wmode=transparent';
            }
        }

        foreach (self::getVimeoPatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]);
                $medialink = 'http://player.vimeo.com/video/'. $sanitized .'?color=00ADEF&fullscreen=1&autoplay=1';
            }
        }

        foreach (self::getGooglePatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]);
                $medialink = 'http://video.google.com/googleplayer.swf?docid='. $sanitized .'&hl=en&fs=true&autoplay=true';
            }
        }

        foreach (self::getMetacafePatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]) ."/". urlencode(strip_tags($matches[2]));
                $medialink = 'http://www.metacafe.com/fplayer/'. $sanitized .'.swf';
            }
        }

        foreach (self::getMyspacePatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]);
                $medialink = 'http://mediaservices.myspace.com/services/media/embed.aspx/m='. $sanitized .',t=1,mt=video,ap=1';
            }
        }

        foreach (self::getDailymotionPatterns() as $pattern) {
            if (preg_match($pattern, $medialink, $matches)) {
                $sanitized = strip_tags($matches[1]);
                $medialink = 'http://www.dailymotion.com/embed/video/'. $sanitized .'?autoPlay=1';
            }
        }

        return urlencode($medialink);
    }


    //--- Static properties ---//

    public static function getObjectWidth() {
        return ModalBoxHelper::getModalWidth() - 20;
    }

    public static function getObjectHeight() {
        return ModalBoxHelper::getModalHeight() - 20;
    }

    public static function getSupportedMedia() {
        return array("asf", "avi", "wm", "wmv", "wma",
                     "dv", "mov", "moov", "movie", "mp4", "mpg", "mpeg",
                     "3gp", "3g2", "m2v", "aac", "m4a",
                     "flv", "f4v", "m4v", "mp3",
                     "swf", "webm", "ogv", "ogg");
    }

    public static function getSupportedImages() {
        return array("jpg", "jpeg", "png", "gif", "bmp");
    }

    public static function getYoutubePatterns() {
        return array('/youtube\.com\/v\/([^&^\?]+)/i',
                     '/youtube\.com\/watch\?v=([^&]+)/i',
                     '/youtube\.com\/embed\/([^&^\?]+)/i',
                     '/youtu\.be\/([^&^\?]+)/i');
    }

    public static function getVimeoPatterns() {
        return array('/http:\/\/vimeo\.com\/([^&^\?]+)/i',
                     '/player\.vimeo\.com\/video\/([^&^\?]+)/i');
    }

    public static function getGooglePatterns() {
        return array('/video\.google\.com\/googleplayer\.swf\?docid=([^&]+)/i',
                     '/video\.google\.com\/videoplay\?docid=([^&]+)/i');
    }

    public static function getMetacafePatterns() {
        return array('/metacafe\.com\/watch\/([^\/]+)\/([^\/]+)/i',
                     '/metacafe\.com\/fplayer\/([^\/]+)\/([^\/]+)\.swf/i');
    }

    public static function getMyspacePatterns() {
        return array('/myspace\.com.*\/video.*\/([0-9]+)/i',
                     '/mediaservices\.myspace\.com\/services\/media\/embed\.aspx\/m=([0-9]+)/i',
                     '/lads\.myspace\.com\/videos\/MSVideoPlayer\.swf\?m=([0-9]+)/i');
    }

    public static function getDailymotionPatterns() {
        return array('/dailymotion\.com.*\/video\/(([^&^\?^_]+))/i');
    }

}