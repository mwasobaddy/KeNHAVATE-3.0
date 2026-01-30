import { useEffect, useRef } from 'react';

declare global {
    interface Window {
        Echo: any;
    }
}

export function useEcho() {
    const echoRef = useRef(window.Echo);

    return echoRef.current;
}

export function usePrivateChannel(channelName: string) {
    const echo = useEcho();
    const channelRef = useRef<any>(null);

    useEffect(() => {
        if (echo && channelName) {
            channelRef.current = echo.private(channelName);
        }

        return () => {
            if (channelRef.current) {
                echo?.leave(channelName);
            }
        };
    }, [echo, channelName]);

    return channelRef.current;
}

export function useChannelEvent(channel: any, event: string, callback: (data: any) => void) {
    useEffect(() => {
        if (channel && event && callback) {
            channel.listen(event, callback);

            return () => {
                channel.stopListening(event, callback);
            };
        }
    }, [channel, event, callback]);
}