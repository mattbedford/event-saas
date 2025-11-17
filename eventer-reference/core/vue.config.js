const { defineConfig } = require('@vue/cli-service');

module.exports = defineConfig({
  transpileDependencies: true,
  chainWebpack : (config) => {
    if (process.env.NODE_ENV === 'production') {
    config.output.filename('js/[name]/eventerWebpack.js');
    config.output.chunkFilename('js/[name]/eventerChunks.js');
    }
  },
});
