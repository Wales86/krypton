<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property string $uuid
 * @property string $market
 * @property double $quantity
 * @property double $price
 * @property double $value
 * @property string $type
 * @property double $stop_loss
 * @property double $start_earn
 * @property string $status
 * @property string $crdate
 */
class Order extends \yii\db\ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_PROCESSED = 'processed';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uuid', 'market', 'quantity', 'price', 'type'], 'required'],
            [['quantity', 'price', 'value', 'stop_loss', 'start_earn'], 'number'],
            [['crdate'], 'safe'],
            [['uuid', 'market', 'type', 'status'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uuid' => 'Uuid',
            'market' => 'Market',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'value' => 'Value',
            'type' => 'Type',
            'stop_loss' => 'Stop Loss',
            'start_earn' => 'Start Earn',
            'status' => 'Status',
            'crdate' => 'Crdate',
        ];
    }

    public function beforeSave($insert)
    {
        $this->crdate = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }
}