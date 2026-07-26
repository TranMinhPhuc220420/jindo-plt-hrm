import type { ImgHTMLAttributes } from 'react';
import { LOGO_SRC } from '@/lib/brand';
import { cn } from '@/lib/utils';

type Props = ImgHTMLAttributes<HTMLImageElement>;

export default function AppLogoIcon({ className, alt = '', ...props }: Props) {
    return (
        <img
            src={LOGO_SRC}
            alt={alt}
            className={cn('object-cover', className)}
            draggable={false}
            {...props}
        />
    );
}
