# PHP实现apple授权web登录

苹果如何生成相关参数，可以参考下面文章

[笔记：PHP实现apple授权web登录]: http://iamzcr.com/web/article/detail/id/40

提供了两种解决方案（method01和method02），两种解决方案的原理是一样的

关于method01，要安装jwt库，需要在已经安装了ruby的情况下执行：

```
gem install jwt
```

然后执行脚本：

```
ruby key.rb
```

特别要注意的是，只能生成有效时长为180天的公钥，如果大于该时间，获取access_token的时候可能会抛出下面错误：

```
invalid_client
```

如果使用上有什么问题，请联系qq：1076686352，或者提交issue