<?php
/**
 * Abstract Model Class
 *
 * Base class for all entity models in the Leave Manager plugin.
 * Provides common functionality for data access and manipulation.
 *
 * @package LeaveManager\Model
 */

namespace LeaveManager\Model;

/**
 * Class AbstractModel
 */
abstract class AbstractModel {

    /**
     * The primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Model attributes
     *
     * @var array
     */
    protected array $attributes = array();

    /**
     * Original attributes (for dirty checking)
     *
     * @var array
     */
    protected array $original = array();

    /**
     * Fillable attributes (mass assignment protection)
     *
     * @var array
     */
    protected array $fillable = array();

    /**
     * Hidden attributes (excluded from toArray)
     *
     * @var array
     */
    protected array $hidden = array();

    /**
     * Date attributes (automatically cast to DateTime)
     *
     * @var array
     */
    protected array $dates = array( 'created_at', 'updated_at' );

    /**
     * Constructor
     *
     * @param array $attributes Initial attributes.
     */
    public function __construct( array $attributes = array() ) {
        $this->fill( $attributes );
        $this->syncOriginal();
    }

    /**
     * Fill the model with an array of attributes
     *
     * @param array $attributes Attributes to fill.
     * @return self
     */
    public function fill( array $attributes ): self {
        foreach ( $attributes as $key => $value ) {
            if ( $this->isFillable( $key ) ) {
                $this->setAttribute( $key, $value );
            }
        }
        return $this;
    }

    /**
     * Check if an attribute is fillable
     *
     * @param string $key Attribute key.
     * @return bool
     */
    protected function isFillable( string $key ): bool {
        // If fillable is empty, all attributes are fillable
        if ( empty( $this->fillable ) ) {
            return true;
        }
        return in_array( $key, $this->fillable, true );
    }

    /**
     * Set an attribute value
     *
     * @param string $key   Attribute key.
     * @param mixed  $value Attribute value.
     * @return self
     */
    public function setAttribute( string $key, $value ): self {
        $this->attributes[ $key ] = $value;
        return $this;
    }

    /**
     * Get an attribute value
     *
     * @param string $key     Attribute key.
     * @param mixed  $default Default value if not set.
     * @return mixed
     */
    public function getAttribute( string $key, $default = null ) {
        return $this->attributes[ $key ] ?? $default;
    }

    /**
     * Magic getter for attributes
     *
     * @param string $key Attribute key.
     * @return mixed
     */
    public function __get( string $key ) {
        return $this->getAttribute( $key );
    }

    /**
     * Magic setter for attributes
     *
     * @param string $key   Attribute key.
     * @param mixed  $value Attribute value.
     */
    public function __set( string $key, $value ): void {
        $this->setAttribute( $key, $value );
    }

    /**
     * Magic isset for attributes
     *
     * @param string $key Attribute key.
     * @return bool
     */
    public function __isset( string $key ): bool {
        return isset( $this->attributes[ $key ] );
    }

    /**
     * Get the primary key value
     *
     * @return mixed
     */
    public function getId() {
        return $this->getAttribute( $this->primary_key );
    }

    /**
     * Get the primary key column name
     *
     * @return string
     */
    public function getPrimaryKey(): string {
        return $this->primary_key;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string {
        return $this->table;
    }

    /**
     * Sync original attributes with current
     *
     * @return self
     */
    public function syncOriginal(): self {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Get dirty (changed) attributes
     *
     * @return array
     */
    public function getDirty(): array {
        $dirty = array();
        foreach ( $this->attributes as $key => $value ) {
            if ( ! array_key_exists( $key, $this->original ) || $this->original[ $key ] !== $value ) {
                $dirty[ $key ] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Check if the model has been modified
     *
     * @return bool
     */
    public function isDirty(): bool {
        return ! empty( $this->getDirty() );
    }

    /**
     * Check if the model exists in the database
     *
     * @return bool
     */
    public function exists(): bool {
        return ! empty( $this->getId() );
    }

    /**
     * Convert the model to an array
     *
     * @return array
     */
    public function toArray(): array {
        $array = $this->attributes;
        
        // Remove hidden attributes
        foreach ( $this->hidden as $key ) {
            unset( $array[ $key ] );
        }
        
        return $array;
    }

    /**
     * Convert the model to JSON
     *
     * @param int $options JSON encode options.
     * @return string
     */
    public function toJson( int $options = 0 ): string {
        return json_encode( $this->toArray(), $options );
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * Create a new model instance from database row
     *
     * @param object|array $row Database row.
     * @return static
     */
    public static function fromRow( $row ): self {
        $attributes = is_object( $row ) ? get_object_vars( $row ) : $row;
        $model = new static( $attributes );
        $model->syncOriginal();
        return $model;
    }
}
