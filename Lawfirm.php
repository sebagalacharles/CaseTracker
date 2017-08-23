<?php

namespace common\models;

use Yii;

use yii\helpers\Url;

/**
 * This is the model class for table "lawfirm".
 *
 * @property integer $id
 * @property string $name
 * @property string $created_at
 * @property integer $firm_admin
 * @property string $code
 * @property string $short_code Short code used when generating  reference numbers
 * @property integer $created_by
 * @property string $logo
 * @property string $updated_at
 * @property string $website
 * @property string $about
 * @property string $address
 * @property string $post_office_box
 * @property string $telephone
 * @property string $email
 */
class Lawfirm extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'casetracker.lawfirm';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name', 'created_at', 'firm_admin', 'code', 'created_by', 'logo', 'updated_at', 'website', 'about', 'address', 'post_office_box', 'telephone', 'email', 'short_code'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['firm_admin', 'created_by'], 'integer'],
            [['about', 'short_code'], 'string'],
            [['name', 'website', 'post_office_box', 'email'], 'string', 'max' => 255],
            [['code', 'logo', 'telephone'], 'string', 'max' => 50],
            [['address'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'short_code' => 'Reference Code',
            'created_at' => 'Created At',
            'firm_admin' => 'Firm Admin',
            'code' => 'Code',
            'created_by' => 'Created By',
            'logo' => 'Logo',
            'updated_at' => 'Updated At',
            'website' => 'Website',
            'about' => 'About',
            'address' => 'Address',
            'post_office_box' => 'Post Office Box',
            'telephone' => 'Telephone',
            'email' => 'Email',
        ];
    }

    public static function getMyTasksAssigned($num = 5) {
        $db = Yii::$app->db;
        Yii::$app->db->open();
        //Logged In User
        $user = Yii::$app->user->identity;
        //Query
        $query = "select * from user_message_v where sent_to={$user['id']} LIMIT $num";

        $tasks = $db->createCommand($query)
                ->queryAll();
        return $tasks;
    }

    /**
     * Legalfirm Subscription Status
     * @return type
     */
    

    public function getSubscription() {
        $db = Yii::$app->main_db;
        $query = "SELECT subs.subscription_plan AS plan,subs.start_date,subs.end_date,subs.number_of_accounts,
                    DATEDIFF(subs.end_date,NOW()) AS days_left,
                    CASE subs.subscription_plan
                        WHEN 'standard' 
                            THEN (SELECT GROUP_CONCAT(code) FROM casetracker.module WHERE subscription_mode ='standard' AND record_status=1)
                        WHEN 'premium' 
                            THEN (SELECT GROUP_CONCAT(code) FROM casetracker.module WHERE subscription_mode IN('standard','premium') AND record_status=1)
                        ELSE
                            (SELECT GROUP_CONCAT(code) FROM casetracker.module WHERE record_status=1) END
                    AS modules
                    FROM casetracker.lawfirm_subscription subs WHERE firm_id='{$this->id}'
                    AND subs.service_subscribed LIKE 'Annual Subscription'
                    AND subs.end_date>NOW()";

        return $db->createCommand($query)->queryOne();
    }

    public function getModulesSubscribedTo() {
        $law_firm_id = Yii::$app->lawfirm->id;
        $db = Yii::$app->main_db;
        
        $query = "SELECT * FROM module WHERE module.subscription_mode='standard' ORDER BY module.name Asc";
        $standard_modules = $db->createCommand($query)->queryAll();

        $query = "SELECT * FROM subscription_details INNER JOIN lawfirm_subscription INNER JOIN module ON lawfirm_subscription.subscription_id=subscription_details.subscription_id && subscription_details.module_id=module.id WHERE lawfirm_subscription.firm_id=$law_firm_id && lawfirm_subscription.end_date >= CURDATE() && module.record_status=1 && module.subscription_mode!='standard' ORDER BY module.name Asc";
        $subcribed_modules = $db->createCommand($query)->queryAll();

        return array_merge($standard_modules, $subcribed_modules);
    }

    public function isFirmSubscribedToModule(){
        $base_url = Yii::$app->request->getUrl(); //explode("/", $rt)[3]
        $runing_module = explode("/", $base_url)[2];
        if(\Yii::$app->user->isGuest){
            return true;
        }
        else{
            if(isset(\Yii::$app->session['modules'])){
                foreach (\Yii::$app->session['modules'] as $module) {
                    if($module['module_url'] == $runing_module){
                        return true;
                    }
                }
            }
            return false;
        } 
    }

    /* added by charles */
    /*public function getModulesSubscribedTo() {
        $law_firm_id = Yii::$app->lawfirm->id;
        $db = Yii::$app->main_db;
        $query = "SELECT * FROM module INNER JOIN lawfirm_subscription ON module.id=lawfirm_subscription.module_id WHERE lawfirm_subscription.firm_id=$law_firm_id && module.record_status=1 || module.subscription_mode='standard' ORDER BY module.name Asc";
        $result = $db->createCommand($query)->queryAll();
        return $result;
    }*/

}
