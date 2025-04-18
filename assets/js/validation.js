/**
 * Add validation support.
 */
/* global MutationObserver, composerPackages */
document.addEventListener( 'DOMContentLoaded', () => {
	const errors = {
		base_path: [],
		name: [],
		title: [],
		type: [],
	};
	let inputs = document.querySelectorAll( '#composer-packages__products input[type="text"]' );
	const submitButton = document.querySelector( '#submit' );

	/**
	 * Submit button click.
	 * Makes sure to validate all fields.
	 *
	 * @param {Event} event The event
	 */
	const doSubmit = ( event ) => {
		event.preventDefault();

		inputs = document.querySelectorAll( '#composer-packages__products input[type="text"]' );
		const hasInvalid = validateAll();

		if ( ! hasInvalid ) {
			submitButton.removeEventListener( 'click', doSubmit );
			submitButton.click();
		}
	};

	/**
	 * Display errors on a field.
	 *
	 * @param {HTMLElement} element   The field element
	 * @param {Array}       errorList A list of errors
	 */
	const displayErrors = ( element, errorList ) => {
		if ( ! errorList.length ) {
			return;
		}

		const errorElement = document.createElement( 'p' );

		errorElement.classList.add( 'composer-packages__error' );

		for ( const error of errorList ) {
			if ( errorElement.innerHTML ) {
				errorElement.innerHTML = errorElement.innerHTML + '<br>' + composerPackages.i18n.errors[ error ];
			} else {
				errorElement.innerHTML = composerPackages.i18n.errors[ error ];
			}
		}

		element.parentNode.append( errorElement );
		element.classList.add( 'composer-packages__has-error' );
	};

	/**
	 * Validate a field.
	 *
	 * @param {HTMLElement} element The field element
	 * @return	{boolean} Whether a field is invalid
	 */
	const validate = ( element ) => {
		let hasInvalid = false;

		const errorElement = element.parentNode.querySelector( '.composer-packages__error' );
		const inputType = element.getAttribute( 'data-input-type' );

		if ( errorElement ) {
			errorElement.remove();
		}

		element.classList.remove( 'composer-packages__has-error' );

		switch ( inputType ) {
			case 'base_path':
			case 'name':
			case 'title':
			case 'type':
				if ( ! element.value ) {
					errors[ inputType ].push( 'empty' );
					hasInvalid = true;
					break;
				}
				break;
		}

		displayErrors( element, errors[ inputType ] );
		errors[ inputType ] = [];

		return hasInvalid;
	};

	/**
	 * Validate all fields.
	 *
	 * @return	{boolean} Whether any field is invalid
	 */
	const validateAll = () => {
		let hasInvalid = false;

		for ( const input of inputs ) {
			const isInvalid = validate( input );

			if ( isInvalid ) {
				hasInvalid = true;
			}
		}

		return hasInvalid;
	};

	for ( const input of inputs ) {
		input.addEventListener( 'blur', ( event ) => validate( event.currentTarget ) );
	}

	submitButton.addEventListener( 'click', doSubmit );

	/**
	 * Input mutation observer.
	 *
	 * @type	{MutationObserver}
	 */
	const inputObserver = new MutationObserver( () => {
		const newInputs = document.querySelectorAll( '#composer-packages__products input[type="text"]' );

		if ( newInputs.length !== inputs.length ) {
			for ( const input of inputs ) {
				input.removeEventListener( 'blur', ( event ) => validate( event.currentTarget ) );
			}

			for ( const input of newInputs ) {
				input.addEventListener( 'blur', ( event ) => validate( event.currentTarget ) );
			}
		}
	} );

	// check for DOM changes
	inputObserver.observe( document, {
		childList: true,
		subtree: true,
	} );
} );
