/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks/src/checkout/index.tsx":
/*!***************************************!*\
  !*** ./blocks/src/checkout/index.tsx ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ \"react\");\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/html-entities */ \"@wordpress/html-entities\");\n/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-registry */ \"@woocommerce/blocks-registry\");\n/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/settings */ \"@woocommerce/settings\");\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _shared_nets_easy_checkout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../shared/nets-easy-checkout */ \"./blocks/src/shared/nets-easy-checkout.tsx\");\n/**\n * External dependencies\n */\n\n/**\n * Wordpress/WooCommerce dependencies\n */\n\n// @ts-ignore - Cant avoid this issue, but its loaded in by Webpack\n\n// @ts-ignore - Cant avoid this issue, but its loaded in by Webpack\n\n\nconst settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__.getSetting)(\"nets_easy_data\", {});\nconst title = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__.decodeEntities)(settings.title || \"Nets Easy\");\n// Loop each key in settings and register the payment method with the key as the name\nObject.keys(settings).forEach((key) => {\n    const { enabled, title, description, icon, buttonLabel } = settings[key];\n    console.log(\"Registering payment method\", key, enabled, title, description, icon);\n    const options = {\n        name: key,\n        label: react__WEBPACK_IMPORTED_MODULE_0__.createElement(_shared_nets_easy_checkout__WEBPACK_IMPORTED_MODULE_4__.Label, { title: title, icon: icon }),\n        content: react__WEBPACK_IMPORTED_MODULE_0__.createElement(_shared_nets_easy_checkout__WEBPACK_IMPORTED_MODULE_4__.NetsEasyCheckout, { description: description }),\n        edit: react__WEBPACK_IMPORTED_MODULE_0__.createElement(_shared_nets_easy_checkout__WEBPACK_IMPORTED_MODULE_4__.NetsEasyCheckout, { description: description }),\n        placeOrderButtonLabel: buttonLabel,\n        canMakePayment: () => enabled,\n        ariaLabel: title,\n    };\n    console.log(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod);\n    (0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod)(options);\n});\n\n\n//# sourceURL=webpack://dibs-easy-for-woocommerce/./blocks/src/checkout/index.tsx?");

/***/ }),

/***/ "./blocks/src/shared/nets-easy-checkout.tsx":
/*!**************************************************!*\
  !*** ./blocks/src/shared/nets-easy-checkout.tsx ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   Label: () => (/* binding */ Label),\n/* harmony export */   NetsEasyCheckout: () => (/* binding */ NetsEasyCheckout)\n/* harmony export */ });\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ \"react\");\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);\n/**\n * External dependencies\n */\n\nconst NetsEasyCheckout = (props) => {\n    const { description } = props;\n    return (react__WEBPACK_IMPORTED_MODULE_0__.createElement(\"div\", null,\n        react__WEBPACK_IMPORTED_MODULE_0__.createElement(\"p\", null, description)));\n};\nconst Label = (props) => {\n    const { title, icon } = props;\n    // Print the title and icon as a single line.\n    return (react__WEBPACK_IMPORTED_MODULE_0__.createElement(\"div\", { style: {\n            display: \"flex\",\n            gap: 16,\n            width: \"100%\",\n            justifyContent: \"space-between\",\n            paddingRight: 16,\n        } },\n        react__WEBPACK_IMPORTED_MODULE_0__.createElement(\"span\", null, title),\n        react__WEBPACK_IMPORTED_MODULE_0__.createElement(\"img\", { src: icon, alt: title })));\n};\n\n\n//# sourceURL=webpack://dibs-easy-for-woocommerce/./blocks/src/shared/nets-easy-checkout.tsx?");

/***/ }),

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./blocks/src/checkout/index.tsx");
/******/ 	
/******/ })()
;