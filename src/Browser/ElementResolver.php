<?php

namespace Puth\Laravel\Browser;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class ElementResolver
{
    use Macroable;
    
    /**
     * The remote web driver instance.
     *
     * @var mixed
     */
    public $puthPage;
    
    /**
     * The selector prefix for the resolver.
     *
     * @var string
     */
    public $prefix;
    
    /**
     * Set the elements the resolver should use as shortcuts.
     *
     * @var array
     */
    public $elements = [];
    
    /**
     * The button finding methods.
     *
     * @var array
     */
    protected $buttonFinders = [
        'findById',
        'findButtonBySelector',
        'findButtonByName',
        'findButtonByValue',
        'findButtonByText',
    ];
    
    /**
     * Create a new element resolver instance.
     *
     * @param mixed $puthPage
     * @param string $prefix
     * @return void
     */
    public function __construct($puthPage, $prefix = '')
    {
        $this->puthPage = $puthPage;
        $this->prefix = trim($prefix);
    }
    
    /**
     * Set the page elements the resolver should use as shortcuts.
     *
     * @param array $elements
     * @return $this
     */
    public function pageElements(array $elements)
    {
        $this->elements = $elements;
        
        return $this;
    }
    
    /**
     * Resolve the element for a given input "field".
     *
     * @param string $field
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveForTyping($field)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        return $this->firstOrFail([
            "input[name='{$field}']", "textarea[name='{$field}']", $field,
        ]);
    }
    
    /**
     * Resolve the element for a given select "field".
     *
     * @param string $field
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveForSelection($field)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        return $this->firstOrFail([
            "select[name='{$field}']", $field,
        ]);
    }
    
    /**
     * Resolve all the options with the given value on the select field.
     *
     * @param string $field
     * @param array $values
     * @return mixed[]
     *
     * @throws \Exception
     */
    public function resolveSelectOptions($field, array $values)
    {
        $options = $this->resolveForSelection($field)
            ->children('option');
        
        if (empty($options)) {
            return [];
        }
        
        return array_filter($options, function ($option) use ($values) {
            return in_array($option->value, $values);
        });
    }
    
    /**
     * Resolve the element for a given radio "field" / value.
     *
     * @param string $field
     * @param string|null $value
     * @return mixed
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function resolveForRadioSelection($field, $value = null)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        if (is_null($value)) {
            throw new InvalidArgumentException(
                "No value was provided for radio button [{$field}]."
            );
        }
        
        return $this->firstOrFail([
            "input[type=radio][name='{$field}'][value='{$value}']", $field,
        ]);
    }
    
    /**
     * Resolve the element for a given checkbox "field".
     *
     * @param string|null $field
     * @param string|null $value
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveForChecking($field, $value = null)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        $selector = 'input[type=checkbox]';
        
        if (!is_null($field)) {
            $selector .= "[name='{$field}']";
        }
        
        if (!is_null($value)) {
            $selector .= "[value='{$value}']";
        }
        
        return $this->firstOrFail([
            $selector, $field,
        ]);
    }
    
    /**
     * Resolve the element for a given file "field".
     *
     * @param string $field
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveForAttachment($field)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        return $this->firstOrFail([
            "input[type=file][name='{$field}']", $field,
        ]);
    }
    
    /**
     * Resolve the element for a given "field".
     *
     * @param string $field
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveForField($field)
    {
        if (!is_null($element = $this->findById($field))) {
            return $element;
        }
        
        return $this->firstOrFail([
            "input[name='{$field}']", "textarea[name='{$field}']",
            "select[name='{$field}']", "button[name='{$field}']", $field,
        ]);
    }
    
    /**
     * Resolve the element for a given button.
     *
     * @param string $button
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function resolveForButtonPress($button)
    {
        foreach ($this->buttonFinders as $method) {
            if (!is_null($element = $this->{$method}($button))) {
                return $element;
            }
        }
        
        throw new InvalidArgumentException(
            "Unable to locate button [{$button}]."
        );
    }
    
    /**
     * Resolve the element for a given button by selector.
     *
     * @param string $button
     * @return mixed|null
     */
    protected function findButtonBySelector($button)
    {
        if (!is_null($element = $this->find($button))) {
            return $element;
        }
    }
    
    /**
     * Resolve the element for a given button by name.
     *
     * @param string $button
     * @return mixed|null
     */
    protected function findButtonByName($button)
    {
        if (!is_null($element = $this->find("input[type=submit][name='{$button}']")) ||
            !is_null($element = $this->find("input[type=button][value='{$button}']")) ||
            !is_null($element = $this->find("button[name='{$button}']"))) {
            return $element;
        }
    }
    
    /**
     * Resolve the element for a given button by value.
     *
     * @param string $button
     * @return mixed|null
     */
    protected function findButtonByValue($button)
    {
        foreach ($this->all('input[type=submit]') as $element) {
            if ($element->getAttribute('value') === $button) {
                return $element;
            }
        }
    }
    
    /**
     * Resolve the element for a given button by text.
     *
     * @param string $button
     * @return mixed|null
     */
    protected function findButtonByText($button)
    {
        foreach ($this->all('button') as $element) {
            if (Str::contains($element->getText(), $button)) {
                return $element;
            }
        }
    }
    
    /**
     * Attempt to find the selector by ID.
     *
     * @param string $selector
     * @return mixed|null
     */
    protected function findById($selector)
    {
        if (preg_match('/^#[\w\-:]+$/', $selector)) {
            return $this->puthPage->get(
                '#' . substr($selector, 1),
                ['timeout' => 0],
            );
        }
    }
    
    /**
     * Find an element by the given selector or return null.
     *
     * @param string $selector
     * @return mixed|null
     */
    public function find($selector)
    {
        try {
            return $this->findOrFail($selector);
        } catch (Exception $e) {
            //
        }
    }
    
    /**
     * Get the first element matching the given selectors.
     *
     * @param array $selectors
     * @return mixed
     *
     * @throws \Exception
     */
    public function firstOrFail($selectors)
    {
        foreach ((array)$selectors as $selector) {
            try {
                return $this->findOrFail($selector);
            } catch (Exception $e) {
                //
            }
        }
        
        throw $e;
    }
    
    /**
     * Find an element by the given selector or throw an exception.
     *
     * @param string $selector
     * @return mixed
     */
    public function findOrFail($selector)
    {
        if (!is_null($element = $this->findById($selector))) {
            return $element;
        }
        
        return $this->puthPage->get(
            $this->format($selector),
            ['timeout' => 0],
        );
    }
    
    /**
     * Find the elements by the given selector or return an empty array.
     *
     * @param string $selector
     * @return mixed[]
     */
    public function all($selector)
    {
        try {
            return $this->puthPage->getAll(
                $this->format($selector),
                ['timeout' => 0],
            );
        } catch (Exception $e) {
            //
        }
        
        return [];
    }
    
    /**
     * Format the given selector with the current prefix.
     *
     * @param string $selector
     * @return string
     */
    public function format($selector)
    {
        $sortedElements = collect($this->elements)->sortByDesc(function ($element, $key) {
            return strlen($key);
        })->toArray();
        
        $selector = str_replace(
            array_keys($sortedElements), array_values($sortedElements), $originalSelector = $selector
        );
        
        if (Str::startsWith($selector, '@') && $selector === $originalSelector) {
            // TODO put Dusk::selectorHtmlAttribute in PuthManager
            $selector = '[' . 'dusk' . '="' . explode('@', $selector)[1] . '"]';
        }
        
        $trimmed = trim($this->prefix . ' ' . $selector);
        
        if ($trimmed === '') {
            // TODO maybe return null and change all usages to return puthPage 
            $trimmed = 'body';
        }
        
        return $trimmed;
    }
}