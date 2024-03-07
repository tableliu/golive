<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "event".
 *
 * @property string $app_id
 * @property string $platform
 * @property integer $etl_tstamp
 * @property integer $collector_tstamp
 * @property integer $dvce_tstamp
 * @property string $event
 * @property string $event_id
 * @property integer $txn_id
 * @property string $name_tracker
 * @property string $v_tracker
 * @property string $v_collector
 * @property string $v_etl
 * @property string $user_id
 * @property string $user_ipaddress
 * @property string $user_fingerprint
 * @property string $domain_userid
 * @property integer $domain_sessionidx
 * @property string $network_userid
 * @property string $geo_country
 * @property string $geo_region
 * @property string $geo_city
 * @property string $geo_zipcode
 * @property double $geo_latitude
 * @property double $geo_longitude
 * @property string $geo_region_name
 * @property string $page_url
 * @property string $page_title
 * @property string $page_referrer
 * @property string $page_urlscheme
 * @property string $page_urlhost
 * @property integer $page_urlport
 * @property string $page_urlpath
 * @property string $page_urlquery
 * @property string $page_urlfragment
 * @property string $refr_urlscheme
 * @property string $refr_urlhost
 * @property integer $refr_urlport
 * @property string $refr_urlpath
 * @property string $refr_urlquery
 * @property string $refr_urlfragment
 * @property string $refr_medium
 * @property string $refr_source
 * @property string $refr_term
 * @property string $mkt_medium
 * @property string $mkt_source
 * @property string $mkt_term
 * @property string $mkt_content
 * @property string $mkt_campaign
 * @property string $contexts
 * @property string $se_category
 * @property string $se_action
 * @property string $se_label
 * @property string $se_property
 * @property double $se_value
 * @property string $unstruct_event
 * @property string $tr_orderid
 * @property string $tr_affiliation
 * @property string $tr_total
 * @property string $tr_tax
 * @property string $tr_shipping
 * @property string $tr_city
 * @property string $tr_state
 * @property string $tr_country
 * @property string $ti_orderid
 * @property string $ti_sku
 * @property string $ti_name
 * @property string $ti_category
 * @property string $ti_price
 * @property integer $ti_quantity
 * @property integer $pp_xoffset_min
 * @property integer $pp_xoffset_max
 * @property integer $pp_yoffset_min
 * @property integer $pp_yoffset_max
 * @property string $useragent
 * @property string $br_name
 * @property string $br_family
 * @property string $br_version
 * @property string $br_type
 * @property string $br_renderengine
 * @property string $br_lang
 * @property integer $br_features_pdf
 * @property integer $br_features_flash
 * @property integer $br_features_java
 * @property integer $br_features_director
 * @property integer $br_features_quicktime
 * @property integer $br_features_realplayer
 * @property integer $br_features_windowsmedia
 * @property integer $br_features_gears
 * @property integer $br_features_silverlight
 * @property integer $br_cookies
 * @property string $br_colordepth
 * @property integer $br_viewwidth
 * @property integer $br_viewheight
 * @property string $os_name
 * @property string $os_family
 * @property string $os_manufacturer
 * @property string $os_timezone
 * @property string $dvce_type
 * @property integer $dvce_ismobile
 * @property integer $dvce_screenwidth
 * @property integer $dvce_screenheight
 * @property string $doc_charset
 * @property integer $doc_width
 * @property integer $doc_height
 * @property string $geo_timezone
 * @property string $mkt_clickid
 * @property string $mkt_network
 * @property string $etl_tags
 * @property integer $dvce_sent_tstamp
 * @property string $domain_sessionid
 */
class Event extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%event}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_id', 'collector_tstamp', 'v_collector'], 'required'],
            [['etl_tstamp', 'collector_tstamp', 'dvce_tstamp', 'txn_id', 'domain_sessionidx',
                'page_urlport', 'refr_urlport', 'ti_quantity', 'pp_xoffset_min', 'pp_xoffset_max',
                'pp_yoffset_min', 'pp_yoffset_max', 'br_features_pdf', 'br_features_flash',
                'br_features_java', 'br_features_director', 'br_features_quicktime', 'br_features_realplayer',
                'br_features_windowsmedia', 'br_features_gears', 'br_features_silverlight', 'br_cookies',
                'br_viewwidth', 'br_viewheight', 'dvce_ismobile', 'dvce_screenwidth', 'dvce_screenheight',
                'doc_width', 'doc_height', 'dvce_sent_tstamp'], 'integer'],
            [['geo_latitude', 'geo_longitude', 'se_value', 'tr_total', 'tr_tax', 'tr_shipping', 'ti_price'], 'number'],
            [['page_url', 'page_referrer', 'contexts', 'unstruct_event'], 'string'],
            [['app_id', 'platform', 'user_id', 'page_urlhost', 'refr_urlhost', 'refr_term',
                'mkt_medium', 'mkt_source', 'mkt_term', 'mkt_content', 'mkt_campaign', 'tr_orderid',
                'tr_affiliation', 'tr_city', 'tr_state', 'tr_country', 'ti_orderid', 'ti_sku', 'ti_name',
                'ti_category', 'br_lang', 'br_colordepth'], 'string', 'max' => 255],
            [['event', 'name_tracker', 'doc_charset', 'mkt_clickid', 'mkt_network'], 'string', 'max' => 128],
            [['event_id', 'domain_userid', 'domain_sessionid'], 'string', 'max' => 36],
            [['v_tracker', 'v_collector', 'v_etl', 'geo_region_name', 'page_urlfragment', 'refr_urlquery'],
                'string', 'max' => 100],
            [['user_ipaddress'], 'string', 'max' => 45],
            [['user_fingerprint', 'refr_source', 'br_name', 'br_family', 'br_version', 'br_type',
                'br_renderengine', 'os_name', 'os_family', 'os_manufacturer', 'os_timezone', 'dvce_type'],
                'string', 'max' => 50],
            [['network_userid'], 'string', 'max' => 38],
            [['geo_country'], 'string', 'max' => 255],
            [['geo_region'], 'string', 'max' => 3],
            [['geo_city'], 'string', 'max' => 75],
            [['geo_zipcode'], 'string', 'max' => 15],
            [['page_title'], 'string', 'max' => 2000],
            [['page_urlscheme', 'refr_urlscheme'], 'string', 'max' => 16],
            [['page_urlpath', 'page_urlquery', 'refr_urlpath', 'refr_urlfragment',
                'se_category', 'se_action', 'se_label', 'se_property', 'useragent'], 'string', 'max' => 1000],
            [['refr_medium'], 'string', 'max' => 25],
            [['geo_timezone'], 'string', 'max' => 64],
            [['etl_tags'], 'string', 'max' => 500]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'app_id' => Yii::t('form', 'App ID'),
            'platform' => Yii::t('form', 'Platform'),
            'etl_tstamp' => Yii::t('form', 'ETL Timestamp'),
            'collector_tstamp' => Yii::t('form', 'Collector Timestamp'),
            'dvce_tstamp' => Yii::t('form', 'Device Timestamp'),
            'event' => Yii::t('form', 'Event'),
            'event_id' => Yii::t('form', 'Event ID'),
            'txn_id' => Yii::t('form', 'Txn ID'),
            'name_tracker' => Yii::t('form', 'Name Tracker'),
            'v_tracker' => Yii::t('form', 'V Tracker'),
            'v_collector' => Yii::t('form', 'V Collector'),
            'v_etl' => Yii::t('form', 'V ETL'),
            'user_id' => Yii::t('form', 'User ID'),
            'user_ipaddress' => Yii::t('form', 'User Ip Address'),
            'user_fingerprint' => Yii::t('form', 'User Fingerprint'),
            'domain_userid' => Yii::t('form', 'Domain User ID'),
            'domain_sessionidx' => Yii::t('form', 'Domain Session Index'),
            'network_userid' => Yii::t('form', 'Network User ID'),
            'geo_country' => Yii::t('form', 'Country'),
            'geo_region' => Yii::t('form', 'Region'),
            'geo_city' => Yii::t('form', 'City'),
            'geo_zipcode' => Yii::t('form', 'Zip Code'),
            'geo_latitude' => Yii::t('form', 'Latitude'),
            'geo_longitude' => Yii::t('form', 'Longitude'),
            'geo_region_name' => Yii::t('form', 'Region Name'),
            'page_url' => Yii::t('form', 'Page Url'),
            'page_title' => Yii::t('form', 'Page Title'),
            'page_referrer' => Yii::t('form', 'Page Referrer'),
            'page_urlscheme' => Yii::t('form', 'Page Url Scheme'),
            'page_urlhost' => Yii::t('form', 'Page Url Host'),
            'page_urlport' => Yii::t('form', 'Page Url Port'),
            'page_urlpath' => Yii::t('form', 'Page Url Path'),
            'page_urlquery' => Yii::t('form', 'Page Url Query'),
            'page_urlfragment' => Yii::t('form', 'Page Url Fragment'),
            'refr_urlscheme' => Yii::t('form', 'Referrer Url Scheme'),
            'refr_urlhost' => Yii::t('form', 'Referrer Url Host'),
            'refr_urlport' => Yii::t('form', 'Referrer Url Port'),
            'refr_urlpath' => Yii::t('form', 'Referrer Url Path'),
            'refr_urlquery' => Yii::t('form', 'Referrer Url Query'),
            'refr_urlfragment' => Yii::t('form', 'Referrer Url Fragment'),
            'refr_medium' => Yii::t('form', 'Referrer Medium'),
            'refr_source' => Yii::t('form', 'Referrer Source'),
            'refr_term' => Yii::t('form', 'Referrer Term'),
            'mkt_medium' => Yii::t('form', 'Marketing Medium'),
            'mkt_source' => Yii::t('form', 'Marketing Source'),
            'mkt_term' => Yii::t('form', 'Marketing Term'),
            'mkt_content' => Yii::t('form', 'Marketing Content'),
            'mkt_campaign' => Yii::t('form', 'Marketing Campaign'),
            'contexts' => Yii::t('form', 'Contexts'),
            'se_category' => Yii::t('form', 'Structured Event Category'),
            'se_action' => Yii::t('form', 'Structured Event Action'),
            'se_label' => Yii::t('form', 'Structured Event Label'),
            'se_property' => Yii::t('form', 'Structured Event Property'),
            'se_value' => Yii::t('form', 'Structured Event Value'),
            'unstruct_event' => Yii::t('form', 'Unstructured Event'),
            'tr_orderid' => Yii::t('form', 'Transaction Order ID'),
            'tr_affiliation' => Yii::t('form', 'Transaction Affiliation'),
            'tr_total' => Yii::t('form', 'Transaction Total'),
            'tr_tax' => Yii::t('form', 'Transaction Tax'),
            'tr_shipping' => Yii::t('form', 'Transaction Shipping'),
            'tr_city' => Yii::t('form', 'Transaction City'),
            'tr_state' => Yii::t('form', 'Transaction State'),
            'tr_country' => Yii::t('form', 'Transaction Country'),
            'ti_orderid' => Yii::t('form', 'Transaction Item Orderid'),
            'ti_sku' => Yii::t('form', 'Transaction Item Sku'),
            'ti_name' => Yii::t('form', 'Transaction Item Name'),
            'ti_category' => Yii::t('form', 'Transaction Item Category'),
            'ti_price' => Yii::t('form', 'Transaction Item Price'),
            'ti_quantity' => Yii::t('form', 'Transaction Item Quantity'),
            'pp_xoffset_min' => Yii::t('form', 'Page Ping Xoffset Min'),
            'pp_xoffset_max' => Yii::t('form', 'Page Ping Xoffset Max'),
            'pp_yoffset_min' => Yii::t('form', 'Page Ping Yoffset Min'),
            'pp_yoffset_max' => Yii::t('form', 'Page Ping Yoffset Max'),
            'useragent' => Yii::t('form', 'User Agent'),
            'br_name' => Yii::t('form', 'Browser Name'),
            'br_family' => Yii::t('form', 'Browser Family'),
            'br_version' => Yii::t('form', 'Browser Version'),
            'br_type' => Yii::t('form', 'Browser Type'),
            'br_renderengine' => Yii::t('form', 'Browser Render Engine'),
            'br_lang' => Yii::t('form', 'Browser Language'),
            'br_features_pdf' => Yii::t('form', 'Browser Features Pdf'),
            'br_features_flash' => Yii::t('form', 'Browser Features Flash'),
            'br_features_java' => Yii::t('form', 'Browser Features Java'),
            'br_features_director' => Yii::t('form', 'Browser Features Director'),
            'br_features_quicktime' => Yii::t('form', 'Browser Features Quicktime'),
            'br_features_realplayer' => Yii::t('form', 'Browser Features Realplayer'),
            'br_features_windowsmedia' => Yii::t('form', 'Browser Features Windowsmedia'),
            'br_features_gears' => Yii::t('form', 'Browser Features Gears'),
            'br_features_silverlight' => Yii::t('form', 'Browser Features Silverlight'),
            'br_cookies' => Yii::t('form', 'Browser Cookies'),
            'br_colordepth' => Yii::t('form', 'Browser Colordepth'),
            'br_viewwidth' => Yii::t('form', 'Browser Viewwidth'),
            'br_viewheight' => Yii::t('form', 'Browser Viewheight'),
            'os_name' => Yii::t('form', 'OS Name'),
            'os_family' => Yii::t('form', 'OS Family'),
            'os_manufacturer' => Yii::t('form', 'OS Manufacturer'),
            'os_timezone' => Yii::t('form', 'OS Timezone'),
            'dvce_type' => Yii::t('form', 'Device Type'),
            'dvce_ismobile' => Yii::t('form', 'Device Ismobile'),
            'dvce_screenwidth' => Yii::t('form', 'Device Screenwidth'),
            'dvce_screenheight' => Yii::t('form', 'Device Screenheight'),
            'doc_charset' => Yii::t('form', 'Document Charset'),
            'doc_width' => Yii::t('form', 'Document Width'),
            'doc_height' => Yii::t('form', 'Document Height'),
            'geo_timezone' => Yii::t('form', 'Timezone'),
            'mkt_clickid' => Yii::t('form', 'Marketing Click ID'),
            'mkt_network' => Yii::t('form', 'Marketing Network'),
            'etl_tags' => Yii::t('form', 'ETL Tags'),
            'dvce_sent_tstamp' => Yii::t('form', 'Device Sent Timestamp'),
            'domain_sessionid' => Yii::t('form', 'Domain Session ID'),
        ];
    }
}
