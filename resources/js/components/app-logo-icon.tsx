import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden
        >
            <text
                x="20"
                y="26"
                textAnchor="middle"
                fontSize="16"
                fontWeight="700"
                fontFamily="ui-sans-serif, system-ui, sans-serif"
                letterSpacing="-0.5"
                fill="currentColor"
            >
                HR
            </text>
        </svg>
    );
}
