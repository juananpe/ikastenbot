FROM nginx:1.15-alpine as base

# Remove default configuration shipped with the nginx image
RUN rm /etc/nginx/conf.d/default.conf

FROM base as dev
# Push the recommended server configuration in the container
COPY ./config/dev.conf /etc/nginx/conf.d/
