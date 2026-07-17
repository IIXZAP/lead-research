FROM nginx:1.27-alpine

COPY default.conf /etc/nginx/conf.d/default.conf

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD wget -q -O- http://localhost/nginx-health || exit 1
