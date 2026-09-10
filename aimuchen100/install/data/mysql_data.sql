INSERT INTO `pre_user_group` (`groupid`, `groupname`, `system`, `purviews`) VALUES
(1, '管理员组', 1, ''),
(2, '主编组', 1, ''),
(3, '编辑组', 1, ''),
(6, '待验证用户组', 1, ''),
(7, '禁止用户组', 1, ''),
(10, '永久VIP', 1, ''),
(11, '注册用户', 1, ''),
(12, 'VIP用户', 1, '');

INSERT INTO `pre_models` (`mid`, `name`, `tablename`, `index_tpl`, `cate_tpl`, `show_tpl`, `system`, `width`, `height`, `icon`) VALUES
(1, '单页', 'page', '', 'page_show.htm', '', 1, 0, 0, ''),
(2, '文章', 'article', 'article_index.htm', 'article_list.htm', 'article_show.htm', 1, 160, 120, 'fa fa-book');

INSERT INTO `pre_kv` (`k`, `v`, `expiry`) VALUES
('link_keywords', '["tag","tag_top","tag_all","comment","index","sitemap","admin","adminpanel","user","space","login","logout","register","static","upload","log","runtime","install","attach","special","search","so"]', 0);
