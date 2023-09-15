const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    mode: 'jit',
    darkMode: 'class',
    content: [
        './resources/js/**/*.{vue,js}'
    ],
    purge: [
        './resources/js/**/*.{vue,js}'
    ],
    future: {
        removeDeprecatedGapUtilities: true,
        purgeLayersByDefault: true,
    },
    theme: {
        screens: {
            'mobile': {
                'max': '640px',
            },
            'sm': '640px',
            'md': '768px',
            'lg': '920px',
            'xl': '1100px',
            '2xl': '1536px',
            '3xl': '1650px',
        },
        extend: {
            spacing: {
                '17':   '4.25rem',
                'logo': '60px',
                'base': '24px'
            },
            zIndex: {
                '1k': '1000',
                '2k': '2000'
            },
            padding: {
                'xs': '1px'
            },
            fontSize: {
                'xxs': '11px',
            },
            fontFamily: {
                'sans': [ 'Nunito', ...defaultTheme.fontFamily.sans ],
            },
            lineHeight: {
                'map-icon': '20px',
            },
            width: {
                'inventory':          '220px',
                'small-alert':        '380px',
                'alert':              '650px',
                'big-alert':          '650px',
                'large-alert':        '1000px',
                'vlarge-alert':       '1200px',
                'character_advanced': '550px',
                '90':                 '90px',
                'map':                '1160px',
                'map-right':          'calc(100% - 1160px)',
                'split':              'calc(50% - 10px)',
                'tp':                 '170px',
                'tp-staff':           '200px',
                'inventory_contents': '660px',
                'inventory_slot':     '100px',
                'map-gauge':          '258px',
                'map-other-gauge':    'calc(100% - 125px)',
                'map-height-ind':     '60px',
                'map-icon':           '20px',
                'ch-button':          '32px',
                'xs-steam':           '150px',
                'iframe':             '750px',
                'avatar':             '50px',
                'screenshot':         '540px'
            },
            height: {
                'side-close':      '40px',
                'side-open-one':   '116px',
                'side-open-two':   '134px',
                'side-open-three': '180px',
                'side-open-four':  '226px',
                'side-open-five':  '272px',
                'max':             'calc(100vh - (210px + 120px))',
                'inventory_slot':  '100px'
            },
            minWidth: {
                'input':   '200px',
                'context': '140px',
                'base':    '24px'
            },
            minHeight: {
                '50': '50px'
            },
            maxHeight: {
                'max':       'calc(100% - 60px)',
                'img':       '500px',
                'modal-max': 'calc(100% - 10rem)',
                'lg':        '38rem'
            },
            listStyleType: {
                'dash': "'–'"
            },
            inset: {
                'attr':  '16.5px',
                'attr2': '118.5px',
                '2px':   '2px'
            },
            colors: {
                // Light & dark.
                'light': defaultTheme.colors.white,
                'dark':  defaultTheme.colors.gray['900'],

                // Map colors
                'map-staff':     '#46A54B',
                'map-police':    '#7469FF',
                'map-ems':       '#FF5959',
                'map-highlight': '#FF6400',

                'discord': '#6C82CE',
                'steam': '#171A21',

                // Rose
                'rose-100': 'rgb(255, 228, 230)',
                'rose-200': 'rgb(254, 205, 211)',
                'rose-300': 'rgb(253, 164, 175)',
                'rose-400': 'rgb(251, 113, 133)',
                'rose-500': 'rgb(244, 63, 94)',
                'rose-600': 'rgb(225, 29, 72)',
                'rose-700': 'rgb(190, 18, 60)',
                'rose-800': 'rgb(159, 18, 57)',
                'rose-900': 'rgb(136, 19, 55)',

                // Lime
                'lime-100': 'rgb(236 252 203)',
                'lime-200': 'rgb(217 249 157)',
                'lime-300': 'rgb(190 242 100)',
                'lime-400': 'rgb(163 230 53)',
                'lime-500': 'rgb(132 204 22)',
                'lime-600': 'rgb(101 163 13)',
                'lime-700': 'rgb(77 124 15)',
                'lime-800': 'rgb(63 98 18)',
                'lime-900': 'rgb(54 83 20)',

                // Teal
                'teal-100': 'rgb(204 251 241)',
                'teal-200': 'rgb(153 246 228)',
                'teal-300': 'rgb(94 234 212)',
                'teal-400': 'rgb(45 212 191)',
                'teal-500': 'rgb(20 184 166)',
                'teal-600': 'rgb(13 148 136)',
                'teal-700': 'rgb(15 118 110)',
                'teal-800': 'rgb(17 94 89)',
                'teal-900': 'rgb(19 78 74)',

                // Theme colors.
                'primary':   defaultTheme.colors.indigo['600'],
                'secondary': defaultTheme.colors.gray['100'],
                'danger':    defaultTheme.colors.red['500'],
                'warning':   defaultTheme.colors.yellow['500'],
                'success':   defaultTheme.colors.green['500'],
                'muted':     defaultTheme.colors.gray['700'],

                // Theme pale colors.
                'primary-pale':   defaultTheme.colors.indigo['100'],
                'secondary-pale': defaultTheme.colors.gray['50'],
                'danger-pale':    defaultTheme.colors.red['100'],
                'warning-pale':   defaultTheme.colors.yellow['100'],
                'success-pale':   defaultTheme.colors.green['100'],

                // Theme colors (dark mode)
                'dark-primary':   defaultTheme.colors.indigo['400'],
                'dark-secondary': defaultTheme.colors.gray['700'],
                'dark-danger':    defaultTheme.colors.red['500'],
                'dark-warning':   defaultTheme.colors.yellow['500'],
                'dark-success':   defaultTheme.colors.green['500'],
                'dark-muted':     defaultTheme.colors.gray['300'],

                // Theme pale colors (dark mode)
                'dark-primary-pale':   defaultTheme.colors.indigo['700'],
                'dark-secondary-pale': defaultTheme.colors.gray['700'],
                'dark-danger-pale':    defaultTheme.colors.red['700'],
                'dark-warning-pale':   defaultTheme.colors.yellow['700'],
                'dark-success-pale':   defaultTheme.colors.green['700'],
            }
        }
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('./resources/js/scripts/tailwind-forms.js')
    ]
}
