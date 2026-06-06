-- Abonnements génériques : un user suit un contenu (news/article) ou une catégorie pour être
-- notifié (nouveau commentaire / nouvelle publication). Polymorphe (content_type, content_id).
-- Tokens content_type en forme URL-safe (a-z0-9-) car routés via {url_title} :
--   news, article, news-category, article-category.

CREATE TABLE IF NOT EXISTS nf_subscriptions (
	id           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id      INT(11) UNSIGNED NOT NULL,
	content_type VARCHAR(50) NOT NULL,
	content_id   INT(11) UNSIGNED NOT NULL,
	created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uniq_subscription (user_id, content_type, content_id),
	KEY idx_target (content_type, content_id),
	CONSTRAINT fk_subscription_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
