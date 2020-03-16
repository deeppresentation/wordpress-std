<?php namespace DP\Wp;

use DP\Std\Core\Special;
use DP\Std\Core\Arr;


class Acf
{
    public static function sanitize_arrays_fields($value)
    {
        $result = $value;
        if (is_array($value)) {
            $result = Special::pseudo_json_encode($value);
        }
        return $result;
    }

    public static function get_table_field_as_assoc_array_of_columns(string $fieldId, $postId = null)
    {
        if (!$postId) $postId = get_the_ID();
        if ($postId)
        {
            $table = get_field($fieldId, $postId, false);
           /* if (is_string($tableIntroSteps))
            {
                $tableIntroSteps = json_decode($tableIntroSteps, true);
            }*/
            if ($table && is_array($table))
            {
                
                return self::decode_raw_to_assoc_array_of_columns($table);
            }
        
           
        }
        return null;
    }

    public static function get_field_pseudojson_content(string $fieldId, string $postId = null)
    {
        if (!$postId) $postId = get_the_ID();
        if ($postId)
        {
            $data = get_field($fieldId, $postId, false);
            if (is_string($data))
            {
                return Special::pseudo_json_decode($data, true);
            }
            else if (is_array($data))
            {
                foreach ($data['b'] as &$row)
                {
                    foreach ($row as &$val)
                    {
                        $decoded = Special::pseudo_json_decode($val['c'], true);
                        $val['c'] = $decoded;
                    }     
                }
                return $data;
            }
        }

    }

    public static function decode_raw_to_assoc_array_of_columns(array $acfTableField)
    {
        if (array_key_exists('h', $acfTableField) && array_key_exists('b', $acfTableField))
        {
            $res = [];
            foreach ($acfTableField['b'] as $rIdx => $rowDataInput)
            {
                $rowData = [];
                foreach ($acfTableField['h'] as $hIdx => $headerItem)
                {
                    $rowData[Arr::sget($headerItem, 'c', $hIdx)] = Arr::sget($rowDataInput, $hIdx . '.c');
                }  
                $res[] = $rowData;  
            }  
            return $res;
        }  
        return null;   
    }

    public static function update_table_field_from_assoc_array_of_columns(array $assoc_array_of_columns, string $fieldId, $postId = null)
    {
        if (!$postId) $postId = get_the_ID();
        if ($postId)
        {
            return WpStd::update_post_meta(
                $postId, 
                $fieldId, 
                Acf::create_table_field_from_assoc_array_of_columns($assoc_array_of_columns)
            );
        }
        return false;
    }

    public static function create_table_field_from_assoc_array_of_columns(array $dataTableColumns, bool $prependRowNameColumn = false) // asociative array of columns
    {
        
        $header = array();
        $dataRows = array();
        if (!empty($dataTableColumns)) {
            $columnKeys = array_keys($dataTableColumns);
            if (!empty($columnKeys)) {
                $rowKeys = array_keys($dataTableColumns[$columnKeys[0]]);

                // header
                if ($prependRowNameColumn) $header[] = array('c' =>'');
                foreach ($columnKeys as $columnKey) {
                    $header[] = array('c' => self::sanitize_arrays_fields($columnKey));
                }
                // data rows
                foreach ($rowKeys as $rowKey) {
                    $row = array();
                    if ($prependRowNameColumn) $row[] =  array('c' => $rowKey);
                    foreach ($dataTableColumns as $dataTableColumn) {
                        $row[] = array('c' => self::sanitize_arrays_fields($dataTableColumn[$rowKey]));
                    }
                    $dataRows[] = $row;
                }
            }
        }
        return [
            'acftf' => array('v' => '1.2.6'),
            'p' => array('o' => array('uh' => 1)),
            'c' => array_fill(0, count($header), array('p' => '')),
            'h' => $header,
            'b' => $dataRows
        ];
    }

    public static function table_encode_from_assoc_array_of_columns(array $dataTableColumns, bool $prependRowNameColumn = false, int $jsonEncodeBitMaskOption = 0) // asociative array of columns
    {
        return json_encode(self::create_table_field_from_assoc_array_of_columns($dataTableColumns, $prependRowNameColumn), $jsonEncodeBitMaskOption);
    }

    public static function clear_relationships(string $relationShipFieldTag, ?int $postId = null)
    {
        if (!$postId) $postId = get_the_ID();
        if ($postId)
        {
            update_field($relationShipFieldTag, [], $postId);     
        }
    }

    public static function get_group_field(string $groupId, string $fieldId, ?int $postId = null, $def = null ){
        if (!$postId) $postId = get_the_ID();
        $res = get_field($groupId . '_' . $fieldId, $postId, false);
        if (!isset($res)) return $def;
        return $res;
    }

    public static function update_group_field(string $groupId, string $fieldId, $valueToSet, ?int $postId = null ){
        if (!$postId) $postId = get_the_ID();
        update_field($groupId . '_' . $fieldId, $valueToSet, $postId);  
    }

    
    public static function append_relationship($relatedPostId, string $relationShipFieldTag, ?int $postId = null)
    {
        if (!$postId) $postId = get_the_ID();
        if ($postId && $relatedPostId)
        {
            $currentRelationships = get_field($relationShipFieldTag, $postId, false);
            if (empty($currentRelationships)) $currentRelationships = [];
            array_push($currentRelationships, $relatedPostId);
            update_field($relationShipFieldTag, array_unique($currentRelationships), $postId);     
        }
    }

    public static function is_empty_table_field($value)
    {
        if (!$value) return true;
        $h = Arr::get($value, 'h', []);
        $b = Arr::get($value, 'b', []);
        $hEmpty = count($h) == 0 || (count($h) == 1 && empty(Arr::sget($h, '0.c')));
        $bEmpty = count($b) == 0 || ( count($b) == 1 && ( count($b[0]) == 0 || count($b[0]) == 1 && empty(Arr::sget($b, '0.0.c'))) );
        return $hEmpty && $bEmpty;
    }
}