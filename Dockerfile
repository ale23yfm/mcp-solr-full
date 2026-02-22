FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY src/ ./src/
COPY mcp-server.php ./

EXPOSE 8080

CMD ["php", "mcp-server.php"]
