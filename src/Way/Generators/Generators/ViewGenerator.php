<?php

namespace Way\Generators\Generators;

use Illuminate\Support\Pluralizer;

class ViewGenerator extends Generator {

    /**
     * Fetch the compiled template for a view
     *
     * @param  string $template Path to template
     * @param  string $name
     * @return string Compiled template
     */
    protected function getTemplate($template, $name)
    {
        $this->template = $this->file->get($template);

        if ($this->needsScaffolding($template))
        {
            return $this->getScaffoldedTemplate($name);
        }

        // Otherwise, just set the file
        // contents to the file name
        return $name;
    }

    /**
     * Get the scaffolded template for a view
     *
     * @param  string $name
     * @return string Compiled template
     */
    protected function getScaffoldedTemplate($name)
    {
        $model = $this->cache->getModelName();  // post
        $models = Pluralizer::plural($model);   // posts
        $Models = ucwords($models);             // Posts
        $Model = Pluralizer::singular($Models); // Post

        // Create and Edit views require form elements
        if ($name === 'create.blade' or $name === 'edit.blade')
        {
            $formElements = $this->makeFormElements();

            $this->template = str_replace('{{formElements}}', $formElements, $this->template);
        }

        // Replace template vars in view
        foreach(array('model', 'models', 'Models', 'Model') as $var)
        {
            $this->template = str_replace('{{'.$var.'}}', $$var, $this->template);
        }

        // And finally create the table rows
        list($headings, $fields, $editAndDeleteLinks, $showdata) = $this->makeTableRows($model);
        $this->template = str_replace('{{headings}}', implode(PHP_EOL."\t\t\t\t", $headings), $this->template);
        $this->template = str_replace('{{showdata}}', implode(PHP_EOL."\t\t\t\t", $showdata) . PHP_EOL . $editAndDeleteLinks, $this->template);
        $this->template = str_replace('{{fields}}', implode(PHP_EOL."\t\t\t\t\t", $fields) . PHP_EOL . $editAndDeleteLinks, $this->template);

        return $this->template;
    }

    /**
     * Create the table rows
     *
     * @param  string $model
     * @return Array
     */
    protected function makeTableRows($model)
    {
        $models = Pluralizer::plural($model); // posts

        $fields = $this->cache->getFields();

        // First, we build the table headings
        $headings = array_map(function($field) {
            return '<th>' . ucwords($field) . '</th>';
        }, array_keys($fields));

        // Second, we build the show
        $showdata = array_map(function($field) use ($model) {
            return "<dt>" . ucwords($field) . "</dt> <dd> {{{ \$$model->$field }}} </dd>" ;
        }, array_keys($fields));

        // And then the rows, themselves
        $fields = array_map(function($field) use ($model) {
            return "<td>{{{ \$$model->$field }}}</td>";
        }, array_keys($fields));

        // Now, we'll add the edit and delete buttons.
        $editAndDelete = <<<EOT
                    <td class="ac"  width="100px">
                    <a href="{{ URL::route('{$models}.show', array(\${$model}->id)) }}"> {{ Form::button('<i class="glyphicon glyphicon-list"></i>', array('class' => 'btn btn-sm')) }} </a>
                            <a href="{{ URL::route('{$models}.edit', array(\${$model}->id)) }}"> {{ Form::button('<i class="glyphicon glyphicon-pencil"></i>', array('class' => 'btn btn-sm')) }} </a>
                            {{ Form::open(array('method' => 'DELETE', 'route' => array('{$models}.destroy', \${$model}->id), 'style'=>'display:inline-block')) }}
                                    {{ Form::button('<i class="glyphicon glyphicon-trash"></i>', array('type' => 'submit', 'class' => 'btn btn-danger btn-sm', 'data-confirm' => 'Yakin mau dihapus?')) }}
                            {{ Form::close() }}
                    </td>
EOT;

        return array($headings, $fields, $editAndDelete, $showdata);
    }

    /**
     * Add Laravel methods, as string,
     * for the fields
     *
     * @return string
     */
    public function makeFormElements()
    {
        $formMethods = array();

        foreach($this->cache->getFields() as $name => $type)
        {
            $formalName = ucwords($name);

            // TODO: add remaining types
            switch($type)
            {
                case 'integer':
                   $element = "{{ Form::input('number', '$name', null, ['class' => 'form-control', 'id' => '$name']) }}";
                    break;

                case 'date':
                   $element = "{{ Form::input('date', '$name', null, ['class' => 'form-control', 'id' => '$name']) }}";
                    break;

                case 'datetime':
                   $element = "{{ Form::input('datetime', '$name', null, ['class' => 'form-control', 'id' => '$name']) }}";
                    break;

                case 'text':
                    $element = "{{ Form::textarea('$name', null, ['class' => 'form-control', 'id' => '$name']) }}";
                    break;

                case 'boolean':
                    $element = "{{ Form::checkbox('$name') }}";
                    break;

                default:
                    $element = "{{ Form::text('$name', null, ['class' => 'form-control', 'id' => '$name']) }}";
                    break;
            }

            // Now that we have the correct $element,
            // We can build up the HTML fragment
            $frag = <<<EOT
                <div class="form-group">
                    {{ Form::label('$name', '$formalName:', ['class' => 'col-sm-3']) }}
                    <div class="col-sm-9">
                        $element
                    </div>
                </div>

EOT;

            $formMethods[] = $frag;
        }

        return implode(PHP_EOL, $formMethods);
    }

}
