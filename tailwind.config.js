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

            'wide': '2100px'
        },
        extend: {
            spacing: {
                '17':   '4.25rem',
                '18':   '4.5rem',
                '29':   '7.25rem',
                'logo': '60px',
                'base': '24px'
            },
            blur: {
                'xs': '2.5px'
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
                'item':               '140px',
                'item-image':         '100px',
                'inventory':          'calc((140px * 5) + (0.75rem * 4) + (2rem * 2))',
                'small-alert':        '380px',
                'alert':              '650px',
                'big-alert':          '650px',
                'large-alert':        '1000px',
                'vlarge-alert':       '1200px',
                '90':                 '90px',
                'map':                '100%',
                'split':              'calc(50% - 10px)',
                'tp':                 '170px',
                'tp-staff':           '200px',
                'map-icon':           '20px',
                'ch-button':          '32px',
                'xs-steam':           '150px',
                'iframe':             '640px',
                'avatar':             '50px',
                'screenshot':         '540px',
                'twitter-img':        '500px',
            },
            height: {
                'side-close':      '37px',
                'max':             'calc(100vh - (210px + 120px))',
                'box':             '15px',
                'twitter-img':     '250px',
            },
            minWidth: {
                'box':     '350px',
                'input':   '200px',
                'context': '150px',
                'base':    '24px'
            },
            minHeight: {
                '50': '50px',
                'base': '10rem'
            },
            maxWidth: {
                '56':    '14rem',
                'large': 'min(90%, 1220px)',
                'px':    '1px'
            },
            maxHeight: {
                'max':             'calc(100% - 60px)',
                'img':             '500px',
                'modal-max':       'calc(100% - 10rem)',
                'lg':              '40rem',
                'statistics':      '15.5rem',
                'statistics-long': '20rem',
                'section':         '46rem',
            },
            listStyleType: {
                'dash': "'–'"
            },
            inset: {
                'attr':  '16.5px',
                'attr2': '118.5px',
                '2px':   '2px'
            },
            animation: {
                'spin-once': 'spin 1s ease-in-out',
            },
            colors: {
                // Light & dark.
                'light': defaultTheme.colors.white,
                'dark':  defaultTheme.colors.gray['900'],

                // Saturated gray variants
                'gray-900v': 'hsl(242, 47%, 34%)',
                'gray-700v': 'hsl(245, 58%, 51%)',

                // Map colors
                'map-staff':     '#46A54B',
                'map-police':    '#7469FF',
                'map-ems':       '#FF5959',
                'map-highlight': '#FF6400',

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
                'lime-100': 'rgb(236, 252, 203)',
                'lime-200': 'rgb(217, 249, 157)',
                'lime-300': 'rgb(190, 242, 100)',
                'lime-400': 'rgb(163, 230, 53)',
                'lime-500': 'rgb(132, 204, 22)',
                'lime-600': 'rgb(101, 163, 13)',
                'lime-700': 'rgb(77, 124, 15)',
                'lime-800': 'rgb(63, 98, 18)',
                'lime-900': 'rgb(54, 83, 20)',

                // Teal
                'teal-100': 'rgb(204, 251, 241)',
                'teal-200': 'rgb(153, 246, 228)',
                'teal-300': 'rgb(94, 234, 212)',
                'teal-400': 'rgb(45, 212, 191)',
                'teal-500': 'rgb(20, 184, 166)',
                'teal-600': 'rgb(13, 148, 136)',
                'teal-700': 'rgb(15, 118, 110)',
                'teal-800': 'rgb(17, 94, 89)',
                'teal-900': 'rgb(19, 78, 74)',

                // Orange
                'orange-100': 'rgb(255, 237, 213)',
                'orange-200': 'rgb(254, 215, 170)',
                'orange-300': 'rgb(253, 186, 116)',
                'orange-400': 'rgb(251, 146, 60)',
                'orange-500': 'rgb(249, 115, 22)',
                'orange-600': 'rgb(234, 88, 12)',
                'orange-700': 'rgb(194, 65, 12)',
                'orange-800': 'rgb(154, 52, 18)',
                'orange-900': 'rgb(124, 45, 18)',

                // Extra dark color variants
                'red-950': 'rgb(69, 10, 10)',
                'yellow-950': 'rgb(69, 26, 3)',
                'green-950': 'rgb(5, 46, 22)',
                'teal-950': 'rgb(4, 47, 46)',
                'gray-950': 'rgb(18, 22, 33)',

                // Specialty colors
                'discord': '#6C82CE',
                'steam': '#171A21',
                'twitch': '#A970FF',
                'twitch-dark': '#8533ff',

                // Input border color
                'input': '#6b7280',

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
