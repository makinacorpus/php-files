
CREATE TABLE "file" (
    "id" uuid NOT NULL,
    "indexed_at" timestamp NOT NULL DEFAULT current_timestamp,
    "created_at" timestamp NOT NULL DEFAULT current_timestamp,
    "modified_at" timestamp NOT NULL DEFAULT current_timestamp,
    "expires_at" date DEFAULT NULL,
    "deleted_at" timestamp DEFAULT NULL,
    "is_deleted" bool DEFAULT false,
    "is_valid" bool NOT NULL DEFAULT true,
    "is_anonymized" bool NOT NULL DEFAULT false,
    "type" varchar(64) NOT NULL,
    "name" varchar(255) NOT NULL,
    "filename" varchar(1024) NOT NULL,
    "filesize" int NOT NULL,
    "mimetype" varchar(64) NOT NULL,
    "sha1sum" varchar(64) DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE "file_attribute" (
    "file_id" uuid NOT NULL,
    "name" varchar(255) NOT NULL,
    "value" text NOT NULL,
    PRIMARY KEY ("file_id", "name"),
    FOREIGN KEY ("file_id")
        REFERENCES "file" ("id")
        ON DELETE CASCADE
);
