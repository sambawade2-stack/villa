-- EXCLUDE USING gist sur availability_blocks en dépend : sans cette
-- extension, la migration qui pose la contrainte anti-double-réservation
-- échoue purement et simplement.
CREATE EXTENSION IF NOT EXISTS btree_gist;
