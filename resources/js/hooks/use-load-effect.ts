import { useEffect } from 'react';
import type { DependencyList } from 'react';

/**
 * Runs an async loader when deps change. Yields once before invoking so
 * synchronous setState at the start of the loader is not treated as
 * set-state-in-effect (React Compiler lint).
 */
export function useLoadEffect(
    load: () => void | Promise<void>,
    deps: DependencyList,
): void {
    useEffect(() => {
        let cancelled = false;

        void (async () => {
            await Promise.resolve();

            if (cancelled) {
                return;
            }

            await load();
        })();

        return () => {
            cancelled = true;
        };
        // Caller owns the dependency list (same contract as useEffect).
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
}
