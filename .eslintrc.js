module.exports = {
	root: true,
	env: {
		node: true,
	},
	globals: {
		t: true,
		n: true,
		OC: true,
		OCA: true,
		Vue: true,
		VueRouter: true,
		$: true
	},
	extends: [
		'eslint:recommended',
		'plugin:vue/recommended',
		'plugin:prettier/recommended',
	],
	rules: {
		'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
		'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
	},
	parserOptions: {
		parser: 'babel-eslint',
	},
	rules: {
		'vue/max-attributes-per-line': [
			'error',
			{
				singleline: 3,
				multiline: {
					max: 3,
					allowFirstLine: true,
				},
			},
		],
	},
}
