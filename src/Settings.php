<?php namespace DP\Wp;

use DP\Std\Core\Arr;
use DP\Std\Html\Html;
use DP\Std\Html\Element;


class Settings
{ 
    public static function append_to_option(string $optionId, string $valueToAppend)
    {
        $currentVal = get_option($optionId);
        if ($currentVal != false) {

            update_option($optionId, $currentVal . $valueToAppend);
        }       
    }

    public static function update_setting_array(string $optionId, string $optionSubFieldId, $value) : bool
    {
        $options = get_option($optionId);      
        if ($options && array_key_exists($optionSubFieldId, $options)) {
            $options[$optionSubFieldId] = $value;
            return update_option($optionId, $options); 
        }  
        return false;  
    }

    public static function get_setting_array_field(string $optionId, string $optionSubFieldId, $def = '')
    {
        $options = get_option($optionId);      
        if ($options && array_key_exists($optionSubFieldId, $options)) {
            return $options[$optionSubFieldId]; 
        }  
        return $def;  
    }

    public static function init_setting_array(string $optionId, string $sectionId, string $sectionTitle, string $textDomain,
        string $page, array $fields = [], $renderDescriptionClb = null, bool $cleanNotSupportedFields = true)
    {
        $options = get_option($optionId, null);          
        if (is_null($options)) {
            if (add_option($optionId, []))
            {
                $options = get_option($optionId); 
            }
        }   
        if (!is_array($options)) {
            if (delete_option($optionId))
            {
                if (add_option($optionId, []))
                {
                    $options = get_option($optionId); 
                }
            }
        }   
        self::add_settings_section($sectionId, $sectionTitle, $textDomain, $options, $page, $fields, $renderDescriptionClb, $cleanNotSupportedFields, $optionId);
        
        register_setting(
            $optionId,
            $optionId
        );
    }

    public static function add_settings_section(string $sectionId, string $title, string $textDomain, array $options,
        string $page, array $fields = [], $renderDescriptionClb = null, bool $cleanNotSupportedFields = true, string $optionId = '')
    {
        add_settings_section(
            $sectionId,                     // ID used to identify this section and with which to register options
            __($title, $textDomain),        // Title to be displayed on the administration page
            $renderDescriptionClb,
            $page,
            [
                'textDomain' => $textDomain
            ]
        );  
        if ($cleanNotSupportedFields && $optionId)
        {
            $ids = [];  
            foreach ($fields as $field)
            {
                $ids[] = $field['id'];
            }
            $filteredOptions = [];
            foreach ($options as $k => $v)
            {
                if (in_array($k, $ids))
                {
                    $filteredOptions[$k] = $v;    
                }
            }
            if (count($filteredOptions) < count($options))
            {
                $options = $filteredOptions;
                update_option($optionId, $options);
            } 
        }
        foreach ($fields as $field)
        {
            self::add_settings_field(
                $field['id'],
                Arr::sget($field, 'title',  $field['id']),
                $textDomain,
                Arr::sget($field, 'defVal', ''),
                $page,
                $sectionId,
                $options,
                Arr::sget($field, 'renderArgs', []),
                $optionId
            );   
            
        }
    }

    public static function add_settings_field(string $id, string $title, string $textDomain, string $defVal, string $page, 
        string $section, array $options, array $renderArgs = [], string $optionId = null)
    {
        if (!array_key_exists($id, $options))
        {
            $options[$id] = $defVal;  
            if (!empty($optionId)) update_option($optionId, $options);  
        }
        if (!array_key_exists('placeholder', $renderArgs))
        {
            $renderArgs['placeholder'] = $defVal;   
        }
        $args = array_merge([
            'page' => $page, 
            'value' => $options[$id], 
            'name' => $id 
        ], $renderArgs);

        add_settings_field(
            $id,						        // ID used to identify the field throughout the theme
            __($title, $textDomain),	        // The label to the left of the option interface element
            'DP\Wp\Settings::render_option',	        // The name of the function responsible for rendering the option interface
            $page,                              // The page on which this option will be displayed
            $section,
            $args
        );     
    }


    public static function render_option(array $args)
    {
        $page = $args['page'];
        $name = $args['name'];

        $size = Arr::sget($args, 'size', 52);
        $type = Arr::sget($args, 'type', 'text');
        $value = Arr::sget($args, 'value', '');
        $placeholder = Arr::sget($args, 'placeholder', '');

        $id = implode('-', [$page, $type, $name]);
        switch ($type)
        {
           case 'text':
                Html::render('input', null, null, null, [
                    'type' => 'text',
                    'id' => $id,
                    'name' => $page.'[' . $name . ']',
                    'value' => $value,
                    'size' => $size,
                    'placeholder' => $placeholder
                ]);
            break;
            case 'checkbox':
                Html::render('input', null, null, null, [
                    'type' => 'checkbox',
                    'id' => $id,
                    'name' => $page.'[' . $name . ']',
                    'value' => '1',
                     checked(1 , $value, false),
                    'size' => $size,
                    'placeholder' => $placeholder
                ]);
                break;
            case 'textarea':   
                Html::render('textarea', null, null, $value, [
                    'id' => $id,
                    'name' => $page.'[' . $name . ']',
                    'cols' => $size,
                    'rows' => Arr::sget($args, 'rowCnt', 5),
                    'placeholder' => $placeholder,
                    //'$1' => 'readonly'
                ]);
                break;
            case 'select':
                $options = Arr::get($args, 'options', []);
                $optionsHtml = [];
                foreach ($options as $option)
                {
                    $valAndLabel = explode(":", $option);
                    if (count($valAndLabel))
                    {
                        $optionVal = $valAndLabel[0];
                        $optionLabel = $optionVal;
                        if (count($valAndLabel) > 1) $optionLabel = $valAndLabel[1];
                        $optionsHtml[] = '<option value="' . $option . '" ' . selected( $value, $option, false ) . '>' . $optionLabel . '</option>';
                    }
                }
                $select = new Element('select', null, [
                    'id' => $id,
                    'name' => $page.'[' . $name . ']',
                    'placeholder' => $placeholder
                ], $optionsHtml);
                $select->render();
                break;
        }
    }
}