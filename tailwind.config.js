const plugin = require("tailwindcss/plugin");
const colors = require('tailwindcss/colors');

module.exports = {
    purge: ["./templates/front/**/*.html","./templates/back/**/*.html", "./assets/**/*.js"],
    darkMode: false, // or 'media' or 'class'
    theme: {
        colors,
        fontSize: {
            "3xs": ".5rem",
            "2xs": ".625rem",
            xs: ".75rem",
            sm: ".875rem",
            base: "1rem",
            lg: "1.125rem",
            xl: "1.25rem",
            "2xl": "1.5rem",
            "3xl": "1.75rem",
            "4xl": "2rem",
            "5xl": "2.25rem",
            "6xl": "2.5rem",
            "7xl": "2.75rem",
            "8xl": "3rem",
            "9xl": "3.25rem",
            "10xl": "3.5rem",
            "11xl": "3.75rem",
            "12xl": "4rem",
            "13xl": "4.25rem",
        },
        extend: {
            colors: {
                current: "currentColor",
                transparent: "transparent",
                primary: "#91D4F6",
                secondary: "#E30513",

                white: {
                    DEFAULT: "#FFF",
                    light: "#F2F2F2",
                    dark: "#F8F8F8",
                    verydark: "#E6E6E6",
                },
                black: {
                    DEFAULT: "#202A3C",
                    light: "#2C3448",
                },
                grey: {
                    light: "#D3CECB",
                    dark: "#C8C3BF",
                    blue: "#BDD0DC"
                },
                campaign: {

                },
                purple: {
                    DEFAULT: "#7D6FFF",
                    light: "#D681EF",
                },
                red: "#FF6383",
                orange: "#FF9F40",
                yellow: {
                    DEFAULT: "#FFD625",
                    light: "#FFCD56",
                },
                green: "#5CCF6E",
                turquoise: "#4BC0C0",
                blue: "#36A2EB",
            },
            screens: {
                "2xl": "1430px",
            },
            lineHeight: {
                "extra-loose": "2.25",
            },
            borderWidth: {
                3: "3px",
            },
            spacing: {
                "3px": "3px",
            },
        },
    },
    variants: {
        extend: {
            backgroundColor: ["checked"],
            borderColor: ["checked"],
            display: ["group-hover"],
            transform: ["group-hover"],
            pointerEvents: ["group-hover"],
            rotate: ["group-hover"],
            opacity: ["disabled", "readonly"],
        },
    },
    plugins: [
        plugin(function ({ addVariant, e }) {
            addVariant("readonly", ({ modifySelectors, separator }) => {
                modifySelectors(({ className }) => {
                    return `.${e(
                        `readonly${separator}${className}`
                    )}[readonly]`;
                });
            });
        }),
    ],
};
