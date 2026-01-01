/**
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    MIT License https://opensource.org/license/mit
 * @note       This was derived from the jquery-simple-color project at https://github.com/recurser/jquery-simple-color under the MIT license
 */

class SimpleColor {
	constructor(element, options = {}) {
		this.element = element;
		this.options = this.mergeOptions(options);
		this.init();
	}

	mergeOptions(options) {
		const defaults = {
			defaultColor: this.element.getAttribute('defaultColor') || '#FFF',
			cellWidth: parseInt(this.element.getAttribute('cellWidth')) || 25,
			cellHeight: parseInt(this.element.getAttribute('cellHeight')) || 25,
			cellMargin: parseInt(this.element.getAttribute('cellMargin')) || 0,
			boxWidth: this.element.getAttribute('boxWidth') || '25px',
			boxHeight: this.element.getAttribute('boxHeight') || '25px',
			columns: parseInt(this.element.getAttribute('columns')) || 8,
			insert: this.element.getAttribute('insert') || 'after',
			colors: [
                '990033', 'ff3366', 'cc0033', 'ff0033', 'ff9999', 'cc3366', 'ffccff', 'cc6699',
                '993366', '660033', 'cc3399', 'ff99cc', 'ff66cc', 'ff99ff', 'ff6699', 'cc0066',
                'ff0066', 'ff3399', 'ff0099', 'ff33cc', 'ff00cc', 'ff66ff', 'ff33ff', 'ff00ff',
                'cc0099', '990066', 'cc66cc', 'cc33cc', 'cc99ff', 'cc66ff', 'cc33ff', '993399',
                'cc00cc', 'cc00ff', '9900cc', '990099', 'cc99cc', '996699', '663366', '660099',
                '9933cc', '660066', '9900ff', '9933ff', '9966cc', '330033', '663399', '6633cc',
                '6600cc', '9966ff', '330066', '6600ff', '6633ff', 'ccccff', '9999ff', '9999cc',
                '6666cc', '6666ff', '666699', '333366', '333399', '330099', '3300cc', '3300ff',
                '3333ff', '3333cc', '0066ff', '0033ff', '3366ff', '3366cc', '000066', '000033',
                '0000ff', '000099', '0033cc', '0000cc', '336699', '0066cc', '99ccff', '6699ff',
                '003366', '6699cc', '006699', '3399cc', '0099cc', '66ccff', '3399ff', '003399',
                '0099ff', '33ccff', '00ccff', '99ffff', '66ffff', '33ffff', '00ffff', '00cccc',
                '009999', '669999', '99cccc', 'ccffff', '33cccc', '66cccc', '339999', '336666',
                '006666', '003333', '00ffcc', '33ffcc', '33cc99', '00cc99', '66ffcc', '99ffcc',
                '00ff99', '339966', '006633', '336633', '669966', '66cc66', '99ff99', '66ff66',
                '339933', '99cc99', '66ff99', '33ff99', '33cc66', '00cc66', '66cc99', '009966',
                '009933', '33ff66', '00ff66', 'ccffcc', 'ccff99', '99ff66', '99ff33', '00ff33',
                '33ff33', '00cc33', '33cc33', '66ff33', '00ff00', '66cc33', '006600', '003300',
                '009900', '33ff00', '66ff00', '99ff00', '66cc00', '00cc00', '33cc00', '339900',
                '99cc66', '669933', '99cc33', '336600', '669900', '99cc00', 'ccff66', 'ccff33',
                'ccff00', '999900', 'cccc00', 'cccc33', '333300', '666600', '999933', 'cccc66',
                '666633', '999966', 'cccc99', 'ffffcc', 'ffff99', 'ffff66', 'ffff33', 'ffff00',
                'ffcc00', 'ffcc66', 'ffcc33', 'cc9933', '996600', 'cc9900', 'ff9900', 'cc6600',
                '993300', 'cc6633', '663300', 'ff9966', 'ff6633', 'ff9933', 'ff6600', 'cc3300',
                '996633', '330000', '663333', '996666', 'cc9999', '993333', 'cc6666', 'ffcccc',
                'ff3333', 'cc3333', 'ff6666', '660000', '990000', 'cc0000', 'ff0000', 'ff3300',
                'cc9966', 'ffcc99', 'ffffff', 'cccccc', '999999', '666666', '333333', '000000',
                '000000', '000000', '000000', '000000', '000000', '000000', '000000', '000000'
			],
			displayColorCode: this.element.getAttribute('displayColorCode') === 'true' || false,
			colorCodeAlign: this.element.getAttribute('colorCodeAlign') || 'center',
			colorCodeColor: this.element.getAttribute('colorCodeColor') || false,
			hideInput: this.element.getAttribute('hideInput') !== 'false',
			onSelect: null,
			onCellEnter: null,
			onClose: null,
			livePreview: false,
			displayCSS: { 'width': '25px', 'height': '25px' },
			chooserCSS: { 'left': '25px', 'border': '0' }
		};

		return Object.assign({}, defaults, options);
	}

	init() {
		this.options.totalWidth = this.options.columns * (this.options.cellWidth + (2 * this.options.cellMargin));
		this.options.totalHeight = Math.ceil(this.options.colors.length / this.options.columns) * (this.options.cellHeight + (2 * this.options.cellMargin));

		const defaultChooserCSS = {
			'border': '1px solid #000',
			'margin': '0 0 0 5px',
			'width': this.options.totalWidth + 'px',
			'height': this.options.totalHeight + 'px',
			'top': '0',
			'left': this.options.boxWidth,
			'position': 'absolute',
			'background-color': '#fff'
		};

		const defaultDisplayCSS = {
			'background-color': this.options.defaultColor,
			'border': '1px solid #000',
			'width': this.options.boxWidth,
			'height': this.options.boxHeight,
			'line-height': this.options.boxHeight,
			'cursor': 'pointer'
		};

		this.options.chooserCSS = Object.assign({}, defaultChooserCSS, this.options.chooserCSS);
		this.options.displayCSS = Object.assign({}, defaultDisplayCSS, this.options.displayCSS);

		this.buildColorPicker();
	}

	getAdaptiveTextColor(hexColor) {
		const matches = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hexColor);
		if (!matches) return '#FFF';

		const r = parseInt(matches[1], 16);
		const g = parseInt(matches[2], 16);
		const b = parseInt(matches[3], 16);
		const isWhite = (0.213 * r / 255) + (0.715 * g / 255) + (0.072 * b / 255) < 0.5;

		return isWhite ? '#FFF' : '#000';
	}

	setColor(displayBox, color, options) {
		const textColor = options.colorCodeColor || this.getAdaptiveTextColor(color);

		displayBox.dataset.color = color;
		Object.assign(displayBox.style, {
			color: textColor,
			textAlign: options.colorCodeAlign,
			backgroundColor: color
		});

		if (options.displayColorCode === true) {
			displayBox.textContent = color;
		}
	}

	buildColorPicker() {
		const container = document.createElement('div');
		container.className = 'simpleColorContainer';
		container.style.position = 'relative';

		const defaultColor = (this.element.value && this.element.value !== '') ? this.element.value : this.options.defaultColor;

		const displayBox = document.createElement('div');
		displayBox.className = 'simpleColorDisplay';
		Object.assign(displayBox.style, this.options.displayCSS);
		this.setColor(displayBox, defaultColor, this.options);
		container.appendChild(displayBox);

		if (this.options.hideInput) {
			this.element.style.display = 'none';
		}

		displayBox.addEventListener('click', (e) => {
			e.stopPropagation();
			this.toggleChooser(container, displayBox);
		});

		if (this.options.insert === 'before') {
			this.element.parentNode.insertBefore(container, this.element);
		} else {
			this.element.parentNode.insertBefore(container, this.element.nextSibling);
		}

		this.element.colorPickerData = { container, displayBox };
	}

	toggleChooser(container, displayBox) {
		const existingChooser = container.querySelector('.simpleColorChooser');

		if (existingChooser) {
			existingChooser.style.display = existingChooser.style.display === 'none' ? 'block' : 'none';
			return;
		}

		const chooser = document.createElement('div');
		chooser.className = 'simpleColorChooser';
		Object.assign(chooser.style, this.options.chooserCSS);
		container.appendChild(chooser);

		this.options.colors.forEach(color => {
			const cell = document.createElement('div');
			cell.className = 'simpleColorCell';
			cell.id = color;
			Object.assign(cell.style, {
				width: this.options.cellWidth + 'px',
				height: this.options.cellHeight + 'px',
				margin: this.options.cellMargin + 'px',
				cursor: 'pointer',
				lineHeight: this.options.cellHeight + 'px',
				fontSize: '1px',
				float: 'left',
				backgroundColor: '#' + color
			});

			if (this.options.onCellEnter || this.options.livePreview) {
				cell.addEventListener('mouseenter', () => {
					if (this.options.onCellEnter) {
						this.options.onCellEnter(color, this.element);
					}
					if (this.options.livePreview) {
						this.setColor(displayBox, '#' + color, this.options);
					}
				});
			}

			cell.addEventListener('click', (e) => {
				e.stopPropagation();
				const selectedColor = '#' + color;
				this.element.value = selectedColor;

				const changeEvent = new Event('change', { bubbles: true });
				this.element.dispatchEvent(changeEvent);

				this.setColor(displayBox, selectedColor, this.options);
				chooser.style.display = 'none';

				if (this.options.displayColorCode) {
					displayBox.textContent = selectedColor;
				}

				if (this.options.onSelect) {
					this.options.onSelect(color, this.element);
				}
			});

			chooser.appendChild(cell);
		});

		const closeHandler = (e) => {
			if (!container.contains(e.target)) {
				chooser.style.display = 'none';
				this.setColor(displayBox, displayBox.dataset.color || this.options.defaultColor, this.options);
				document.removeEventListener('click', closeHandler);

				if (this.options.onClose) {
					this.options.onClose(this.element);
				}
			}
		};

		setTimeout(() => {
			document.addEventListener('click', closeHandler);
		}, 0);
	}

	static init(selector, options) {
		const elements = typeof selector === 'string' ? document.querySelectorAll(selector) : [selector];
		elements.forEach(element => {
			if (!element.simpleColorInstance) {
				element.simpleColorInstance = new SimpleColor(element, options);
			}
		});
	}

	closeChooser() {
		if (this.element.colorPickerData) {
			const chooser = this.element.colorPickerData.container.querySelector('.simpleColorChooser');
			if (chooser) {
				chooser.style.display = 'none';
			}
		}
	}
}

document.addEventListener('DOMContentLoaded', function() {
	SimpleColor.init('.color_select', {
		colors: [
			'e11d21', 'eb6420', 'fbca04', '009800', '006b75', '207de5', '0052cc', '5319e7',
			'f7c6c7', 'fad8c7', 'fef2c0', 'bfe5bf', 'bfdadc', 'c7def8', 'bfd4f2', 'd4c5f9'
		],
		cellWidth: 25,
		cellHeight: 25,
		cellMargin: 0,
		columns: 8,
		displayCSS: { 'width': '25px', 'height': '25px' },
		chooserCSS: { 'left': '25px', 'border': '0' },
		onSelect: function(hex, element) {
			const displayElement = document.getElementById(element.id + '_display');
			if (displayElement) {
				displayElement.value = '#' + hex;
			}
		}
	});
});
