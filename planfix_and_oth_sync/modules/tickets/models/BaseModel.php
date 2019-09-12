<?php

namespace app\modules\tickets\models;

class BaseModel
{
    public function __construct(array $associateFields = [])
    {
        foreach ($associateFields as $fieldName => $fieldValue) {
            $this->{$fieldName} = $fieldValue;
        }
    }
}
