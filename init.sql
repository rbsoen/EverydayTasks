create table categories (
	id varchar(32),
	title varchar(64) not null,
	color int not null,

	primary key (id)
);

create table activities (
    id varchar(32),
    subject text not null,
    description text,
    date_time datetime not null,
    category varchar(32),

    primary key (id),
    foreign key (category) references categories(id)
);

create table tasks (
    id varchar(32),
    subject text not null,
    description text,
    due datetime not null,
    category varchar(32),
    activity varchar(32),

    primary key (id),
    foreign key (category) references categories(id)
);