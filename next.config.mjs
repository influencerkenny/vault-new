/** @type {import('next').NextConfig} */
const nextConfig = {
  eslint: {
    ignoreDuringBuilds: true,
  },
  typescript: {
    ignoreBuildErrors: true,
  },
  images: {
    unoptimized: true,
  },
  async rewrites() {
    return [
      {
        source: '/php-api/:path*',
        destination: 'http://localhost/vault-new/api/:path*',
      },
    ];
  },
}

export default nextConfig
