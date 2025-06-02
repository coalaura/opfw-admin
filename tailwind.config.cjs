const defaultTheme = require("tailwindcss/defaultTheme"),
    colors = require("./tailwind.colors.cjs");

module.exports = {
    mode: "jit",
    darkMode: "class",
    content: [
        "./resources/js/**/*.{vue,js}"
    ],
    purge: [
        "./resources/js/**/*.{vue,js}"
    ],
    future: {
        removeDeprecatedGapUtilities: true,
        purgeLayersByDefault: true,
    },
    theme: {
        screens: {
            "mobile": {
                "max": "640px",
            },
            "sm": "640px",
            "md": "768px",
            "lg": "920px",
            "xl": "1100px",
            "2xl": "1536px",
            "3xl": "1650px",

            "wide": "2100px"
        },
        extend: {
            transitionProperty: {
                "position": "top, left, bottom, right"
            },
            spacing: {
                "17":   "4.25rem",
                "18":   "4.5rem",
                "29":   "7.25rem",
                "logo": "60px",
                "base": "24px"
            },
            blur: {
                "xs": "2.5px"
            },
            zIndex: {
                "1k": "1000",
                "2k": "2000"
            },
            padding: {
                "xs": "1px",
                "13": "3.25rem",
            },
            fontSize: {
                "xxs": "11px",
            },
            fontFamily: {
                "sans": [ "Nunito", ...defaultTheme.fontFamily.sans ],
            },
            lineHeight: {
                "map-icon": "20px",
                "5.5":      "1.4rem"
            },
            width: {
                "item":               "140px",
                "item-image":         "100px",
                "inventory":          "calc((140px * 5) + (0.75rem * 4) + (2rem * 2))",
                "small-alert":        "380px",
                "alert":              "650px",
                "big-alert":          "650px",
                "large-alert":        "1000px",
                "vlarge-alert":       "1200px",
                "90":                 "90px",
                "map":                "100%",
                "split":              "calc(50% - 10px)",
                "tp":                 "170px",
                "tp-staff":           "200px",
                "map-icon":           "20px",
                "ch-button":          "32px",
                "xs-steam":           "150px",
                "iframe":             "640px",
                "avatar":             "50px",
                "screenshot":         "540px",
                "twitter-img":        "500px",
                "520":                "520px",
                "chat-full":          "27rem",
            },
            height: {
                "side-close":      "37px",
                "max":             "calc(100vh - (210px + 120px))",
                "box":             "15px",
                "twitter-img":     "250px",
                "outfit":          "500px",
            },
            minWidth: {
                "box":     "350px",
                "input":   "200px",
                "context": "150px",
                "base":    "24px",
                "chart":   "25%"
            },
            minHeight: {
                "50": "50px",
                "base": "10rem"
            },
            maxWidth: {
                "56":     "14rem",
                "large":  "min(90%, 1220px)",
                "px":     "1px",
                "40":     "10rem",
            },
            maxHeight: {
                "max":             "calc(100% - 60px)",
                "img":             "500px",
                "modal-max":       "calc(100% - 10rem)",
                "lg":              "40rem",
                "statistics":      "15.5rem",
                "statistics-long": "20rem",
                "section":         "46rem",
            },
            listStyleType: {
                "dash": "'–'"
            },
            inset: {
                "attr":  "16.5px",
                "attr2": "118.5px",
                "2px":   "2px"
            },
            animation: {
                "spin-once": "spin 1s ease-in-out",
            },
            gridTemplateColumns: {
                "stream": "max-content 1fr max-content",
                "auto-6":   "repeat(auto-fill, minmax(1.5rem, 1fr))"
            },
            gridTemplateRows: {
                "min": "min-content"
            },
            cursor: {
                "resize": "w-resize",
                "none":   "none"
            },
            colors: {
                // Light & dark.
                "light": defaultTheme.colors.white,
                "dark":  defaultTheme.colors.gray["900"],

                "lightbg": defaultTheme.colors.gray["100"],
                "darkbg":  defaultTheme.colors.gray["800"],
                "lightbd": defaultTheme.colors.gray["200"],
                "darkbd":  defaultTheme.colors.gray["700"],
                "vdarkbg": "rgb(18, 22, 33)", // gray-950

                // Saturated gray variants
                "gray-900v": "hsl(242, 47%, 34%)",
                "gray-700v": "hsl(245, 58%, 51%)",

                // Map colors
                "map-staff":     "#46A54B",
                "map-police":    "#7469FF",
                "map-ems":       "#FF5959",
                "map-highlight": "#FF6400",

                ...colors,

                // Special color variants
                "gray-850": "rgb(24 34 47)",

                // Specialty colors
                "discord": "#6C82CE",
                "steam": "#171A21",
                "twitch": "#A970FF",
                "twitch-dark": "#8533ff",

                // Input border color
                "input": "#6b7280",

                // Theme colors.
                "primary":   colors.indigo[600],
                "secondary": colors.gray[100],
                "danger":    colors.red[500],
                "warning":   colors.yellow[500],
                "success":   colors.green[500],
                "muted":     colors.gray[700],

                // Theme pale colors.
                "primary-pale":   colors.indigo[100],
                "secondary-pale": colors.gray[50],
                "danger-pale":    colors.red[100],
                "warning-pale":   colors.yellow[100],
                "success-pale":   colors.green[100],

                // Theme colors (dark mode)
                "dark-primary":   colors.indigo[400],
                "dark-secondary": colors.gray[700],
                "dark-danger":    colors.red[500],
                "dark-warning":   colors.yellow[500],
                "dark-success":   colors.green[500],
                "dark-muted":     colors.gray[300],

                // Theme pale colors (dark mode)
                "dark-primary-pale":   colors.indigo[700],
                "dark-secondary-pale": colors.gray[700],
                "dark-danger-pale":    colors.red[700],
                "dark-warning-pale":   colors.yellow[700],
                "dark-success-pale":   colors.green[700],

                "code-background": "#161b22",
                "code-base": "#ecf2f8",
                "code-muted": "#89929b",
                "code-border": "#21262d",
                "code-red": "#fa7970",
                "code-orange": "#faa356",
                "code-green": "#7ce38b",
                "code-lightblue": "#a2d2fb",
                "code-blue": "#77bdfb",
                "code-purple": "#cea5fb",
            }
        }
    },
    plugins: [
        require("@tailwindcss/typography"),
        require("./resources/js/scripts/tailwind-forms.cjs"),
    ]
}
